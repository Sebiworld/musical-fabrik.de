<?php
namespace ProcessWire;

require_once wire('config')->paths->templates . 'components/forms_component/form_submission_guard.class.php';

/**
 * Route `page-unlock/{id}`: exchanges the password of a page locked by
 * PageAccessPassword for an unlock key.
 *
 * Failed attempts are limited per client and page. The password is read from
 * the request body only (never from the URL) and is never logged or returned.
 */
class PageUnlockApi {
	/** Failed attempts per client and page within ATTEMPT_WINDOW seconds. */
	const ATTEMPT_LIMIT = 10;

	const ATTEMPT_WINDOW = 3600;

	/** Cache prefix of the failed attempt counters (separate from the form counters). */
	const ATTEMPT_CACHE_PREFIX = 'page-unlock-';

	public static function unlockPage($data) {
		$passwordModule = wire('modules')->get('PageAccessPassword');

		// Missing, hidden and unprotected pages answer alike, so the response
		// does not reveal whether a page exists.
		$id   = isset($data->id) ? (int) $data->id : 0;
		$page = $id > 0 ? wire('pages')->get('id=' . $id) : new NullPage();
		if (!$passwordModule || !$page->id || !$page->viewable() || !$passwordModule->isLocked($page)) {
			throw new NotFoundException();
		}

		$password = self::passwordFromBody();
		if ($password === null) {
			throw new AppApiException("Required parameter: 'password' missing!", 400, ['errorcode' => 'missing_required_parameter']);
		}

		// The lock is held from the limit check until a failed attempt is counted.
		$guard = new FormSubmissionGuard($page->id, FormSubmissionGuard::clientIp($_SERVER), self::ATTEMPT_LIMIT, self::ATTEMPT_WINDOW, self::ATTEMPT_CACHE_PREFIX);
		if (!$guard->lock() || $guard->isLimitReached()) {
			$retryAfter = max(1, $guard->retryAfter());
			$guard->unlock();
			header('Retry-After: ' . $retryAfter);
			throw new AppApiException('Too many attempts. Please try again later.', 429, ['errorcode' => 'too_many_requests']);
		}

		if (!$passwordModule->checkPassword($page, $password)) {
			$guard->recordSubmission();
			$guard->unlock();
			throw new AppApiException('Wrong password.', 403, ['errorcode' => 'wrong_password']);
		}
		$guard->unlock();

		$user = wire('user');
		if ($user->isLoggedin()) {
			$passwordModule->grantForUser($page, $user);
		}

		return $passwordModule->createUnlockKey($page);
	}

	/**
	 * The field `password` of the JSON or form body. Null if it is missing,
	 * empty or not a string (a JSON number counts as its digits).
	 */
	protected static function passwordFromBody(): ?string {
		$value = null;

		$body = json_decode((string) @file_get_contents('php://input'), true);
		if (is_array($body) && array_key_exists('password', $body)) {
			$value = $body['password'];
		} elseif (isset($_POST['password'])) {
			$value = $_POST['password'];
		}

		if (is_int($value)) {
			$value = (string) $value;
		}

		return is_string($value) && $value !== '' ? $value : null;
	}
}
