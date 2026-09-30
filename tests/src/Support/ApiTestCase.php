<?php

declare(strict_types=1);
namespace Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that talk to the running API test server over HTTP.
 *
 * The base URL comes from MF_TEST_API_URL (default https://127.0.0.1:8001/api/).
 * TLS verification is disabled since the test server uses a local
 * certificate, and no cookies are sent or stored.
 *
 * The API key is resolved at runtime from the appapi_apikeys table (never
 * hardcoded). It is looked up for application id DEFAULT_APPLICATION_ID
 * ("MF App"), unless MF_TEST_API_APP_ID overrides it. If no usable key is
 * found, tests are skipped rather than failed.
 */
abstract class ApiTestCase extends TestCase {
	// "MF App" (id 2). Not id 1 ("MF Onsite JS"): that application is the
	// legacy variant and no longer in operation.
	private const DEFAULT_APPLICATION_ID = 2;

	private static ?string $resolvedBaseUrl = null;
	private static ?string $resolvedApiKey = null;
	private static ?string $skipReason = null;
	private static bool $resolved = false;

	protected function setUp(): void {
		parent::setUp();
		self::resolveOnce();

		if (self::$skipReason !== null) {
			$this->markTestSkipped(self::$skipReason);
		}
	}

	private static function resolveOnce(): void {
		if (self::$resolved) {
			return;
		}
		self::$resolved = true;

		self::$resolvedBaseUrl = getenv('MF_TEST_API_URL') ?: 'https://127.0.0.1:8001/api/';

		if (!self::isServerReachable(self::$resolvedBaseUrl)) {
			self::$skipReason = 'API test server not reachable at ' . self::$resolvedBaseUrl
				. '. Start it with: symfony server:start --port=8001 --daemon (from the backend directory). '
				. 'symfony server:start only allows one server per directory, so stop a running dev server first.';
			return;
		}

		$applicationId = self::resolveApplicationId();

		$apiKey = self::lookUpApiKey($applicationId);
		if ($apiKey === null) {
			self::$skipReason = 'No usable API key found for application id ' . $applicationId . '. Set '
				. 'MF_TEST_API_APP_ID to an application id that has an accessible key in appapi_apikeys.';
			return;
		}

		self::$resolvedApiKey = $apiKey;
	}

	private static function isServerReachable(string $baseUrl): bool {
		$ch = curl_init($baseUrl);
		curl_setopt_array($ch, [
			CURLOPT_NOBODY => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 20,
		]);
		curl_exec($ch);
		$errno = curl_errno($ch);
		curl_close($ch);

		// Any response (even an HTTP error status) means the server answered;
		// only a connection-level failure (errno !== 0) means it's unreachable.
		return $errno === 0;
	}

	private static function resolveApplicationId(): int {
		$envAppId = getenv('MF_TEST_API_APP_ID');
		if ($envAppId !== false && $envAppId !== '') {
			return (int) $envAppId;
		}

		return self::DEFAULT_APPLICATION_ID;
	}

	private static function lookUpApiKey(int $applicationId): ?string {
		$db = \ProcessWire\wire('database');

		$stmt = $db->prepare(
			'SELECT `key` FROM appapi_apikeys ' .
			'WHERE application_id = :application_id ' .
			'AND (accessible_until IS NULL OR accessible_until > NOW()) ' .
			'ORDER BY id LIMIT 1'
		);
		$stmt->execute(['application_id' => $applicationId]);
		$key = $stmt->fetchColumn();

		return $key === false ? null : (string) $key;
	}

	/**
	 * @return array{status: int, json: mixed, raw: string}
	 */
	protected function apiRequest(string $method, string $path, array $extraHeaders = []): array {
		$url = rtrim(self::$resolvedBaseUrl, '/') . '/' . ltrim($path, '/');

		$headers = array_merge(['X-API-KEY: ' . self::$resolvedApiKey], $extraHeaders);

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_CUSTOMREQUEST => strtoupper($method),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_COOKIE => null,
		]);

		$raw = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$json = ResponseBody::decode(
			(string) $raw,
			static::class . '::' . $this->name() . ' (' . strtoupper($method) . ' ' . $path . ')'
		);

		return ['status' => $status, 'json' => $json, 'raw' => (string) $raw];
	}
}
