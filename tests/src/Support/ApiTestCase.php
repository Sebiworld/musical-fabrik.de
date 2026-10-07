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
	 * `headers` maps lower-case response header names to their values; a
	 * header sent more than once keeps its last value. `$body` is sent as the
	 * request body as given (e.g. an http_build_query() string).
	 *
	 * @return array{status: int, json: mixed, raw: string, headers: array<string, string>}
	 */
	protected function apiRequest(string $method, string $path, array $extraHeaders = [], ?string $body = null): array {
		$url = rtrim(self::$resolvedBaseUrl, '/') . '/' . ltrim($path, '/');

		$headers = array_merge(['X-API-KEY: ' . self::$resolvedApiKey], $extraHeaders);

		$responseHeaders = [];
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseHeaders): int {
				$parts = explode(':', $line, 2);
				if (count($parts) === 2) {
					$responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
				}

				return strlen($line);
			},
			CURLOPT_CUSTOMREQUEST => strtoupper($method),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_COOKIE => null,
		]);
		if ($body !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}

		$raw = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$json = ResponseBody::decode(
			(string) $raw,
			static::class . '::' . $this->name() . ' (' . strtoupper($method) . ' ' . $path . ')'
		);

		return ['status' => $status, 'json' => $json, 'raw' => (string) $raw, 'headers' => $responseHeaders];
	}

	/**
	 * Sends several requests at the same time and waits for all of them.
	 * Each request is [method, path, headers, body]. Returns status and raw
	 * body per request, in the given order.
	 *
	 * @param array<int, array{0: string, 1: string, 2?: array, 3?: ?string}> $requests
	 * @return array<int, array{status: int, raw: string}>
	 */
	protected function apiRequestsInParallel(array $requests): array {
		$multi = curl_multi_init();
		$handles = [];
		foreach ($requests as $index => $request) {
			$ch = curl_init(rtrim(self::$resolvedBaseUrl, '/') . '/' . ltrim($request[1], '/'));
			curl_setopt_array($ch, [
				CURLOPT_CUSTOMREQUEST => strtoupper($request[0]),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 60,
				CURLOPT_HTTPHEADER => array_merge(['X-API-KEY: ' . self::$resolvedApiKey], $request[2] ?? []),
				CURLOPT_COOKIE => null,
				// One connection per request, so the server handles them in parallel.
				CURLOPT_FORBID_REUSE => true,
				CURLOPT_FRESH_CONNECT => true,
			]);
			if (isset($request[3])) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $request[3]);
			}
			curl_multi_add_handle($multi, $ch);
			$handles[$index] = $ch;
		}

		do {
			$result = curl_multi_exec($multi, $running);
			if ($running) {
				curl_multi_select($multi, 1.0);
			}
		} while ($running && $result === CURLM_OK);

		$responses = [];
		foreach ($handles as $index => $ch) {
			$responses[$index] = [
				'status' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
				'raw' => (string) curl_multi_getcontent($ch),
			];
			curl_multi_remove_handle($multi, $ch);
			curl_close($ch);
		}
		curl_multi_close($multi);

		return $responses;
	}

	/**
	 * Asserts the shape of the `seo` object: title, description, canonical
	 * URL (absolute, frontend path with trailing slash, no query), image
	 * (absolute URL or null) and noindex. Returns the object.
	 *
	 * @return array<string, mixed>
	 */
	protected static function assertSeoShape(mixed $seo, string $expectedPath): array {
		self::assertIsArray($seo);
		self::assertSame(['title', 'description', 'canonical', 'image', 'noindex'], array_keys($seo));
		self::assertIsString($seo['title']);
		self::assertNotSame('', $seo['title']);
		self::assertIsString($seo['description']);
		self::assertDoesNotMatchRegularExpression('/[<>]/', $seo['description']);
		self::assertIsString($seo['canonical']);
		self::assertMatchesRegularExpression('#^https://[^/?]+' . preg_quote($expectedPath, '#') . '$#', $seo['canonical']);
		self::assertTrue($seo['image'] === null || (is_string($seo['image']) && preg_match('#^https?://#', $seo['image']) === 1), 'image');
		self::assertIsBool($seo['noindex']);

		return $seo;
	}

	/**
	 * Asserts that a client cannot tell two responses apart: same status,
	 * same body (byte for byte) and the same headers. Only the `date` header
	 * and the random session id in `set-cookie` may differ.
	 */
	protected static function assertIndistinguishable(array $expected, array $actual, string $message = ''): void {
		self::assertSame($expected['status'], $actual['status'], $message . ' (status)');
		self::assertSame($expected['raw'], $actual['raw'], $message . ' (body)');
		self::assertSame(self::comparableHeaders($expected['headers']), self::comparableHeaders($actual['headers']), $message . ' (headers)');
	}

	/**
	 * @param array<string, string> $headers
	 * @return array<string, string>
	 */
	private static function comparableHeaders(array $headers): array {
		unset($headers['date']);
		if (isset($headers['set-cookie'])) {
			$headers['set-cookie'] = preg_replace('/^([^=]+)=[^;]*/', '$1=<id>', $headers['set-cookie']);
		}
		ksort($headers);

		return $headers;
	}
}
