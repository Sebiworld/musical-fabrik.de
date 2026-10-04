<?php

declare(strict_types=1);
namespace Tests\Support;

use ProcessWire\Apptoken;

use function ProcessWire\wire;

/**
 * Creates AppApi access tokens (double JWT) for test users without a login
 * request: a refresh token is stored in the test database and an access
 * token is signed for it. Remove the refresh tokens again with deleteAll().
 */
final class AccessTokens {
	/** @var Apptoken[] */
	private static array $refreshTokens = [];

	/**
	 * Returns the `Authorization` header for requests as the given user.
	 */
	public static function authorizationHeader(int $userId): string {
		$user = wire('users')->get('id=' . $userId);
		if (!$user->id || $user->isGuest()) {
			throw new \RuntimeException('Test user ' . $userId . ' not found.');
		}

		$application = wire('modules')->get('AppApi')->getApplication(self::applicationId());
		if (!$application || $application->getAuthtype() !== \ProcessWire\Application::authtypeDoubleJWT) {
			throw new \RuntimeException('Application ' . self::applicationId() . ' does not use double JWT authentication.');
		}

		$refreshToken = new Apptoken($application->getID());
		$refreshToken->setUser($user);
		$refreshToken->setCreatedUser($user);
		$refreshToken->setModifiedUser($user);
		$refreshToken->setExpirationTime(time() + 3600);
		if (!$refreshToken->save()) {
			throw new \RuntimeException('The refresh token could not be saved.');
		}
		self::$refreshTokens[] = $refreshToken;

		$accessToken = new Apptoken($application->getID());
		$accessToken->setUser($user);
		$accessToken->setExpirationTime(time() + 3600);
		$jwt = $accessToken->getJWT($application->getAccesstokenSecret(), ['rtkn' => $refreshToken->getTokenID()]);

		return 'Authorization: Bearer ' . $jwt;
	}

	/**
	 * Id of a superuser of the test database.
	 */
	public static function superuserId(): int {
		$user = wire('users')->get('roles=superuser, sort=id');
		if (!$user->id) {
			throw new \RuntimeException('The test database has no superuser.');
		}

		return $user->id;
	}

	public static function deleteAll(): void {
		foreach (self::$refreshTokens as $refreshToken) {
			$refreshToken->delete();
		}
		self::$refreshTokens = [];
	}

	private static function applicationId(): int {
		$envAppId = getenv('MF_TEST_API_APP_ID');

		return $envAppId !== false && $envAppId !== '' ? (int) $envAppId : 2;
	}
}
