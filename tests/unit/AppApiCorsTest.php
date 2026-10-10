<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\AppApiHelper;
use ProcessWire\Auth;
use ProcessWire\Router;

use function ProcessWire\wire;

/**
 * AppApi only sends Access-Control-* headers to origins on the configured
 * list (wildcards allowed within one host label). An empty list mirrors every
 * origin as before, and disabling the automatic headers wins over the list.
 * The "authorization" query parameter can be switched off.
 */
final class AppApiCorsTest extends TestCase {
	private const PATTERN = 'https://mfapp-*.vercel.app';

	public static function setUpBeforeClass(): void {
		require_once wire('config')->paths->AppApi . 'classes/Router.php';
	}

	public function testExactOriginGetsHeaders(): void {
		$headers = Router::getCorsHeaders('https://www.example.com', "https://www.example.com\n" . self::PATTERN);

		$this->assertContains('Access-Control-Allow-Origin: https://www.example.com', $headers);
		$this->assertContains('Access-Control-Allow-Credentials: true', $headers);
		$this->assertContains('Vary: Origin', $headers);
	}

	public function testPatternMatchesOneHostLabel(): void {
		$headers = Router::getCorsHeaders('https://mfapp-git-feature-abc.vercel.app', self::PATTERN);

		$this->assertContains('Access-Control-Allow-Origin: https://mfapp-git-feature-abc.vercel.app', $headers);
	}

	public function testPatternDoesNotSpanDots(): void {
		$headers = Router::getCorsHeaders('https://mfapp-x.evil.com.vercel.app', self::PATTERN);

		$this->assertSame(['Vary: Origin'], $headers);
	}

	public function testWildcardNeedsAtLeastOneCharacter(): void {
		$this->assertFalse(AppApiHelper::isOriginAllowed('https://mfapp-.vercel.app', [self::PATTERN]));
		$this->assertTrue(AppApiHelper::isOriginAllowed('https://mfapp-a.vercel.app', [self::PATTERN]));
	}

	public function testHostIsComparedCaseInsensitively(): void {
		$this->assertTrue(AppApiHelper::isOriginAllowed('https://WWW.Example.COM', ['https://www.example.com']));
		$this->assertTrue(AppApiHelper::isOriginAllowed('https://MFAPP-Preview.Vercel.App', [self::PATTERN]));
	}

	public function testDifferentPortOrSchemeIsRejected(): void {
		$allowed = ['https://www.example.com', 'http://localhost:3000'];

		$this->assertFalse(AppApiHelper::isOriginAllowed('https://www.example.com:8443', $allowed));
		$this->assertFalse(AppApiHelper::isOriginAllowed('http://localhost:3001', $allowed));
		$this->assertFalse(AppApiHelper::isOriginAllowed('http://www.example.com', $allowed));
		$this->assertTrue(AppApiHelper::isOriginAllowed('http://localhost:3000', $allowed));
		$this->assertSame(['Vary: Origin'], Router::getCorsHeaders('https://www.example.com:8443', implode("\n", $allowed)));
	}

	public function testInvalidOriginIsRejected(): void {
		$this->assertFalse(AppApiHelper::isOriginAllowed('null', ['https://www.example.com']));
		$this->assertFalse(AppApiHelper::isOriginAllowed('https://www.example.com.evil.com', ['https://www.example.com']));
		$this->assertFalse(AppApiHelper::isOriginAllowed('https://evil.com/https://www.example.com', ['https://www.example.com']));
	}

	public function testEmptyListMirrorsEveryOrigin(): void {
		foreach ([null, '', "  \n \r\n"] as $emptyList) {
			$headers = Router::getCorsHeaders('https://evil.example', $emptyList);

			$this->assertSame([
				'Access-Control-Allow-Origin: https://evil.example',
				'Access-Control-Allow-Headers: Content-Type, AUTHORIZATION, X-API-KEY',
				'Access-Control-Allow-Credentials: true'
			], $headers);
		}
	}

	public function testNoOriginNoAccessControlHeaders(): void {
		$this->assertSame([], Router::getCorsHeaders(null, ''));
		$this->assertSame(['Vary: Origin'], Router::getCorsHeaders(null, self::PATTERN));
	}

	public function testPreflightOnlyForAllowedOrigins(): void {
		$server = [
			'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
			'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type, x-api-key'
		];
		$expected = [
			'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, HEAD, OPTIONS',
			'Access-Control-Allow-Headers: content-type, x-api-key'
		];

		$allowed = Router::getPreflightHeaders($server + ['HTTP_ORIGIN' => 'https://mfapp-abc.vercel.app'], null, self::PATTERN);
		$this->assertSame($expected, $allowed);

		$rejected = Router::getPreflightHeaders($server + ['HTTP_ORIGIN' => 'https://evil.example'], null, self::PATTERN);
		$this->assertSame([], $rejected);

		$withoutOrigin = Router::getPreflightHeaders($server, null, self::PATTERN);
		$this->assertSame([], $withoutOrigin);

		// Empty list: unchanged behavior
		$unrestricted = Router::getPreflightHeaders($server + ['HTTP_ORIGIN' => 'https://evil.example'], ['GET'], '');
		$this->assertSame([
			'Access-Control-Allow-Methods: GET, HEAD, OPTIONS',
			'Access-Control-Allow-Headers: content-type, x-api-key'
		], $unrestricted);
	}

	public function testDisabledAutomaticHeadersWin(): void {
		$this->assertSame([], Router::getCorsHeaders('https://mfapp-abc.vercel.app', self::PATTERN, true));
		$this->assertSame([], Router::getCorsHeaders('https://evil.example', '', true));

		// Preflight keeps its previous behavior, the list is not applied
		$server = ['HTTP_ORIGIN' => 'https://evil.example', 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET'];
		$this->assertSame(
			['Access-Control-Allow-Methods: GET, POST, PUT, DELETE, HEAD, OPTIONS'],
			Router::getPreflightHeaders($server, null, self::PATTERN, true)
		);
	}

	public function testAuthorizationQueryParameterCanBeDisabled(): void {
		$query = ['authorization' => 'Bearer query-token-value'];

		$this->assertSame('query-token-value', Auth::extractBearerToken(null, $query, true));
		$this->assertNull(Auth::extractBearerToken(null, $query, false));
		$this->assertSame('header-token-value', Auth::extractBearerToken('Bearer header-token-value', $query, false));
	}

	public function testLoginSelectorKeepsInternationalizedEmails(): void {
		$this->assertSame('email=bob@müller.example', Auth::getLoginUserSelector('email', 'bob@müller.example'));
		$this->assertSame('email=bøb@example.com', Auth::getLoginUserSelector('email', 'bøb@example.com'));
		$this->assertSame('email=bob@example.com', Auth::getLoginUserSelector('email', 'bob@example.com'));
	}

	public function testLoginSelectorRejectsSelectorInjection(): void {
		$this->assertNull(Auth::getLoginUserSelector('email', 'x@example.com, roles=superuser'));
		$this->assertNull(Auth::getLoginUserSelector('email', ''));
		$this->assertNull(Auth::getLoginUserSelector('password', 'x'));

		$nameSelector = Auth::getLoginUserSelector('name', 'admin, roles=superuser');
		$this->assertIsString($nameSelector);
		$this->assertStringNotContainsString(',', $nameSelector);
		$this->assertStringNotContainsString('roles=', $nameSelector);
	}

	public function testRandomStringKeepsFormat(): void {
		$value = AppApiHelper::generateRandomString(42);

		$this->assertSame(42, strlen($value));
		$this->assertMatchesRegularExpression('/^[0-9a-zA-Z]+$/', $value);
	}
}
