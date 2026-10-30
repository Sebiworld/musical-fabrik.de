<?php
namespace ProcessWire;

/**
 * Keeps the XML sitemap file of SeoMaestro (module setting `sitemapPath`)
 * current. SeoMaestro itself writes the file only during admin page views,
 * at most every `sitemapCache` minutes, and never writes a sitemap without
 * entries, so an outdated file could stay in place for good (e.g. on servers
 * with $config->noindex, which leave every page out).
 *
 * Here the file is marked as outdated whenever a page changes, and the end of
 * the next web request rewrites it. Without changes it is rewritten after
 * MAX_AGE seconds, so that release times of PageAccessReleasetime show up.
 * The content is the one of SeoMaestro and its hooks in site/ready.php
 * (public pages only, PublicVisibilityService).
 */
class SitemapService extends TwackComponent {
	/** Maximum age of the sitemap file in seconds. */
	const MAX_AGE = 3600;

	/** Pause after a failed attempt in seconds. */
	const RETRY_DELAY = 900;

	/** Empty sitemap, written when no page belongs into it. */
	const EMPTY_SITEMAP = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xhtml=\"http://www.w3.org/1999/xhtml\">\n</urlset>\n";

	/** @var string|null Path of the sitemap file, null for the SeoMaestro setting */
	protected $path = null;

	/**
	 * Absolute path of the sitemap file, or an empty string if SeoMaestro
	 * does not manage a sitemap.
	 * @return string
	 */
	public function getPath() {
		if ($this->path !== null) {
			return $this->path;
		}

		$modules = $this->wire('modules');
		if (!$modules->isInstalled('SeoMaestro')) {
			return '';
		}

		$seoMaestro = $modules->get('SeoMaestro');
		if (!$seoMaestro->get('sitemapEnable') || !$seoMaestro->get('sitemapPath')) {
			return '';
		}

		return $this->wire('config')->paths->root . ltrim($seoMaestro->get('sitemapPath'), DIRECTORY_SEPARATOR);
	}

	/**
	 * Uses another file than the one of the SeoMaestro setting.
	 * @param string|null $path absolute path, null for the setting
	 */
	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * True if the sitemap file is missing, marked as outdated or older than
	 * MAX_AGE.
	 * @return bool
	 */
	public function isOutdated() {
		$path = $this->getPath();
		if ($path === '') {
			return false;
		}

		clearstatcache(true, $path);
		if (!is_file($path)) {
			return true;
		}

		return filemtime($path) < time() - self::MAX_AGE;
	}

	/**
	 * Marks the sitemap file as outdated (modification time 0), so that the
	 * next web request rewrites it. Works across processes, also for changes
	 * made on the command line.
	 */
	public function markOutdated() {
		$path = $this->getPath();
		if ($path !== '' && is_file($path)) {
			@touch($path, 0);
			clearstatcache(true, $path);
		}
	}

	/**
	 * True if a change of the page can change the sitemap. Pages of the admin
	 * tree (users, roles, ...) and form entries cannot.
	 * @param Page $page
	 * @return bool
	 */
	public function isRelevantChange(Page $page) {
		if (!$page->id || $page->rootParent()->id === (int) $this->wire('config')->adminRootPageID) {
			return false;
		}

		$template = $page->template->name;

		return $template !== 'forms_container' && strpos($template, 'form_') !== 0;
	}

	/**
	 * True while a failed attempt is less than RETRY_DELAY seconds ago. The
	 * marker is set before every attempt and removed after a successful one,
	 * so it also stays after a fatal error or a timeout, which no code of the
	 * attempt itself could catch.
	 * @return bool
	 */
	public function isRetryBlocked() {
		$marker = $this->getRetryMarkerPath();
		clearstatcache(true, $marker);

		return is_file($marker) && filemtime($marker) > time() - self::RETRY_DELAY;
	}

	/**
	 * Whether the end of the current request may write the sitemap: only if
	 * the response can be finished first (FastCGI), otherwise only in admin
	 * requests, so that no guest or API request has to wait for it.
	 * @param bool $canFinishRequest fastcgi_finish_request() is available
	 * @param bool $adminRequest the request is an admin page view of a logged-in user
	 * @return bool
	 */
	public function mayRefreshAtEndOfRequest($canFinishRequest, $adminRequest) {
		return $canFinishRequest || $adminRequest;
	}

	/**
	 * Rewrites an outdated sitemap file at the end of a web request, after
	 * the response has been sent where possible. Command line scripts
	 * (imports, tests) only mark the file as outdated, the web server writes
	 * it.
	 * @return bool true if the file was written
	 */
	public function refreshAtEndOfRequest() {
		if (PHP_SAPI === 'cli' || !$this->isOutdated() || $this->isRetryBlocked()) {
			return false;
		}

		$canFinishRequest = function_exists('fastcgi_finish_request');
		if (!$this->mayRefreshAtEndOfRequest($canFinishRequest, $this->isAdminRequest())) {
			return false;
		}

		// Release the session lock first, so that the next request of the
		// same user (e.g. the redirect after saving a page) does not wait.
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
		if ($canFinishRequest) {
			fastcgi_finish_request();
		}

		return $this->write();
	}

	/**
	 * Writes the sitemap file with the current content of SeoMaestro, or an
	 * empty sitemap if no page belongs into it. The file is replaced in one
	 * step and only by a complete sitemap, so that a failed attempt keeps the
	 * existing file. Output and warnings of the generation never reach the
	 * response; problems are written to the log `sitemap`. If another request
	 * is already writing, or a failed attempt is less than RETRY_DELAY ago,
	 * nothing happens.
	 * @return bool true if the file was written
	 */
	public function write() {
		$path = $this->getPath();
		if ($path === '' || !is_writable(dirname($path))) {
			return false;
		}

		$lock = @fopen($this->getWorkPath('.lock'), 'c');
		if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
			if ($lock) {
				fclose($lock);
			}
			return false;
		}

		$tmp = $this->getTempPath();
		$marker = $this->getRetryMarkerPath();
		$messages = [];
		$itemCount = null;
		$hookId = null;
		ob_start();
		set_error_handler(function ($severity, $message, $file, $line) use (&$messages) {
			$messages[] = $message . ' (' . basename($file) . ':' . $line . ')';

			return true;
		});

		try {
			if ($this->isRetryBlocked()) {
				return false;
			}

			// Leftovers of attempts that ended with a fatal error or a timeout.
			foreach (glob($this->getWorkPath('-*.xml')) ?: [] as $leftover) {
				@unlink($leftover);
			}
			touch($marker);

			// Number of entries after all hooks, to tell an empty sitemap
			// from a failed write: SeoMaestro returns false for both.
			$hookId = $this->wire()->addHookAfter('SeoMaestro::sitemapItems', function (HookEvent $event) use (&$itemCount) {
				$itemCount = is_array($event->return) ? count($event->return) : null;
			}, ['priority' => 10000]);

			$generated = $this->wire('modules')->get('SeoMaestro')->getSitemapManager()->generate($tmp);
			if (!$generated) {
				if (!$this->isEmptyResult($generated, $itemCount)) {
					throw new \RuntimeException('The sitemap could not be generated.');
				}
				if (file_put_contents($tmp, self::EMPTY_SITEMAP) === false) {
					throw new \RuntimeException('The empty sitemap could not be written.');
				}
			}

			clearstatcache(true, $tmp);
			$content = is_file($tmp) ? (string) file_get_contents($tmp) : '';
			if (strpos($content, '</urlset>') === false) {
				throw new \RuntimeException('The generated sitemap is incomplete.');
			}
			if (!rename($tmp, $path)) {
				throw new \RuntimeException('The sitemap could not be moved into place.');
			}

			@unlink($marker);

			return true;
		} catch (\Throwable $e) {
			$messages[] = $e->getMessage();

			return false;
		} finally {
			if ($hookId !== null) {
				$this->wire()->removeHook($hookId);
			}
			restore_error_handler();
			ob_end_clean();
			if (is_file($tmp)) {
				@unlink($tmp);
			}
			if (!empty($messages)) {
				$this->wire('log')->save('sitemap', 'Sitemap ' . basename($path) . ': ' . implode(' | ', array_slice($messages, 0, 5)));
			}
			clearstatcache(true, $path);
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	/**
	 * True if SeoMaestro wrote nothing because no page belongs into the
	 * sitemap. It returns false as well when writing the file failed, which
	 * must keep the existing sitemap instead of emptying it.
	 * @param bool $generated return value of SitemapManager::generate()
	 * @param int|null $itemCount number of entries after all hooks, null if unknown
	 * @return bool
	 */
	public function isEmptyResult($generated, $itemCount) {
		return !$generated && $itemCount === 0;
	}

	/**
	 * Temp file of the current process, replaced into the sitemap path.
	 * @return string
	 */
	public function getTempPath() {
		return $this->getWorkPath('-' . getmypid() . '.xml');
	}

	/**
	 * Marker of the last attempt that did not succeed.
	 * @return string
	 */
	public function getRetryMarkerPath() {
		return $this->getWorkPath('.retry');
	}

	/**
	 * Work file in the cache directory, separate per sitemap path.
	 * @param string $suffix
	 * @return string
	 */
	protected function getWorkPath($suffix) {
		return $this->wire('config')->paths->cache . 'sitemap-' . substr(md5($this->getPath()), 0, 8) . $suffix;
	}

	/**
	 * True for an admin page view of a logged-in user (no API request).
	 * @return bool
	 */
	protected function isAdminRequest() {
		$page = $this->wire('page');
		if (!($page instanceof Page) || $page->template->name !== 'admin' || !$this->wire('user')->isLoggedin()) {
			return false;
		}

		$appApi = $this->wire('modules')->get('AppApi');

		return !($appApi instanceof AppApi && $appApi->isApiCall());
	}
}
