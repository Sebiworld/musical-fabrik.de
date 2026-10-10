<?php
namespace ProcessWire;

/**
 * Spam protection for form submissions.
 *
 * - Honeypot: an input that is hidden from people. Bots that fill it get a
 *   success response, but nothing is stored or sent.
 * - Limit: at most LIMIT accepted submissions per client and form within
 *   WINDOW seconds. Further submissions are rejected before anything is saved.
 *
 * Counters live in WireCache (table `caches`) and expire with the window. The
 * cache name contains a keyed hash of the client address, never the address
 * itself.
 */
class FormSubmissionGuard {
	/** Name of the honeypot input. It must not match a field of any form template. */
	public const HONEYPOT_FIELD = 'website';

	/** Accepted submissions per client and form within WINDOW seconds. */
	public const LIMIT = 30;

	public const WINDOW = 3600;

	public const CACHE_PREFIX = 'form-submissions-';

	/** Log for dropped honeypot submissions (form id and time only). */
	public const HONEYPOT_LOG = 'form-honeypot';

	/** Seconds a request waits for a parallel request of the same client and form. */
	public const LOCK_TIMEOUT = 5;

	protected $formId;
	protected $clientIp;
	protected $limit;
	protected $window;
	protected $cachePrefix;
	protected $locked = false;

	/**
	 * @param string|null $cachePrefix Counters with another prefix are separate from the form counters
	 *                                 (e.g. failed attempts per page). Defaults to CACHE_PREFIX.
	 */
	public function __construct(int $formId, string $clientIp, int $limit = self::LIMIT, int $window = self::WINDOW, ?string $cachePrefix = null) {
		$this->formId      = $formId;
		$this->clientIp    = $clientIp;
		$this->limit       = max(1, $limit);
		$this->window      = max(1, $window);
		$this->cachePrefix = $cachePrefix !== null && $cachePrefix !== '' ? $cachePrefix : static::CACHE_PREFIX;
	}

	/**
	 * True if the honeypot input was sent with any non-empty value.
	 */
	public static function isHoneypotFilled(WireInputData $post): bool {
		$value = $post->get(self::HONEYPOT_FIELD);
		if (is_array($value)) {
			$value = implode('', array_map(fn ($item) => is_scalar($item) ? (string) $item : 'x', $value));
		}
		return trim((string) $value) !== '';
	}

	/**
	 * Determines the client address for the limit.
	 *
	 * REMOTE_ADDR is used as is when it is a public address: then the request
	 * came straight from the client, or a proxy has already replaced it with
	 * the client address, and a client-supplied X-Forwarded-For header must not
	 * be trusted. Only when REMOTE_ADDR is a private or reserved address (a
	 * reverse proxy on the same host or network) is X-Forwarded-For read, from
	 * right to left: entries on the right were appended by the proxies, so the
	 * first public address from the right is the client. Entries on the left
	 * are client-supplied and are never reached while a proxy-appended public
	 * address follows them.
	 */
	public static function clientIp(array $server): string {
		$remoteAddr = trim((string) ($server['REMOTE_ADDR'] ?? ''));
		if (!self::isValidIp($remoteAddr)) {
			return $remoteAddr === '' ? 'unknown' : $remoteAddr;
		}

		$forwardedFor = trim((string) ($server['HTTP_X_FORWARDED_FOR'] ?? ''));
		if ($forwardedFor === '' || self::isPublicIp($remoteAddr)) {
			return $remoteAddr;
		}

		$hops = array_reverse(array_map('trim', explode(',', $forwardedFor)));
		foreach ($hops as $hop) {
			if (!self::isValidIp($hop)) {
				break;
			}
			if (self::isPublicIp($hop)) {
				return $hop;
			}
		}

		return $remoteAddr;
	}

	/**
	 * Serializes the requests of this client for this form, so that parallel
	 * requests cannot all pass the limit check before any of them is counted.
	 * Hold the lock from the check until the submission is recorded. It is
	 * released by unlock(), and at the latest when the request ends.
	 *
	 * @return bool false if the lock could not be acquired within LOCK_TIMEOUT
	 */
	public function lock(int $timeout = self::LOCK_TIMEOUT): bool {
		if ($this->locked) {
			return true;
		}

		$query = wire('database')->prepare('SELECT GET_LOCK(:name, :timeout)');
		$query->execute(['name' => $this->lockName(), 'timeout' => $timeout]);
		$this->locked = (int) $query->fetchColumn() === 1;

		if ($this->locked) {
			register_shutdown_function([$this, 'unlock']);
		}
		return $this->locked;
	}

	public function unlock(): void {
		if (!$this->locked) {
			return;
		}
		$this->locked = false;

		try {
			$query = wire('database')->prepare('SELECT RELEASE_LOCK(:name)');
			$query->execute(['name' => $this->lockName()]);
		} catch (\Throwable $e) {
			// The database releases named locks when the connection closes.
		}
	}

	public function isLimitReached(?int $now = null): bool {
		return count($this->recentSubmissions($now ?? time())) >= $this->limit;
	}

	/**
	 * Seconds until the oldest counted submission leaves the window.
	 */
	public function retryAfter(?int $now = null): int {
		$now    = $now ?? time();
		$recent = $this->recentSubmissions($now);
		if (empty($recent)) {
			return 0;
		}
		return max(1, min($recent) + $this->window - $now);
	}

	/**
	 * Counts an accepted submission of this client for this form.
	 */
	public function recordSubmission(?int $now = null): void {
		$now      = $now ?? time();
		$recent   = $this->recentSubmissions($now);
		$recent[] = $now;
		wire('cache')->save($this->cacheName(), array_values($recent), $this->window);
	}

	/**
	 * Removes the counters of all clients for a form.
	 */
	public static function clearForm(int $formId): void {
		wire('cache')->delete(static::CACHE_PREFIX . $formId . '-*');
	}

	protected function recentSubmissions(int $now): array {
		$stored = wire('cache')->get($this->cacheName());
		if (!is_array($stored)) {
			return [];
		}

		$since = $now - $this->window;
		return array_values(array_filter(array_map('intval', $stored), fn ($time) => $time > $since));
	}

	protected function cacheName(): string {
		$salt = (string) wire('config')->userAuthSalt;
		$hash = substr(hash_hmac('sha256', self::bucket($this->clientIp), $salt !== '' ? $salt : self::class), 0, 32);
		return $this->cachePrefix . $this->formId . '-' . $hash;
	}

	/**
	 * Named locks are server-wide (max. 64 characters), so the database name is part of the hash.
	 */
	protected function lockName(): string {
		return 'form-submission-' . md5(wire('config')->dbName . '|' . $this->cacheName());
	}

	/**
	 * IPv6 clients usually control a whole /64 network, so they share one counter.
	 */
	protected static function bucket(string $ip): string {
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
			return bin2hex(substr(inet_pton($ip), 0, 8)) . '/64';
		}
		return $ip;
	}

	protected static function isValidIp(string $ip): bool {
		return filter_var($ip, FILTER_VALIDATE_IP) !== false;
	}

	protected static function isPublicIp(string $ip): bool {
		return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
	}
}
