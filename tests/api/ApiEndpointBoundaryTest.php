<?php

declare(strict_types=1);
namespace Tests\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

/**
 * AppApi answers a request only if its path is the endpoint itself or starts
 * with "<endpoint>/". A path that merely begins with the same letters
 * ("/apiXYZ/") is an ordinary URL and ends in the same 404 as an unknown URL,
 * without JSON. Read-only.
 */
final class ApiEndpointBoundaryTest extends ApiTestCase {
	private const UNKNOWN_PATH = '/test-fixture-endpoint-does-not-exist/';

	/**
	 * @return array<string, array{string}>
	 */
	public static function pathsOutsideTheEndpoint(): array {
		return [
			'longer first segment with slash' => ['/apiXYZ/'],
			'longer first segment without slash' => ['/apiXYZ'],
			'longer first segment with dash' => ['/api-test-fixture/'],
			'longer first segment with a route below' => ['/apiXYZ/tpage/test-fixture-content'],
			'longer first segment with query' => ['/apiXYZ?x=1'],
		];
	}

	#[DataProvider('pathsOutsideTheEndpoint')]
	public function testPathStartingWithTheEndpointLettersIsNotAnApiRequest(string $path): void {
		$response = $this->siteRequest($path);
		$unknown = $this->siteRequest(self::UNKNOWN_PATH);

		self::assertSame(404, $unknown['status'], $unknown['raw']);
		self::assertSame(404, $response['status'], $response['raw']);
		self::assertSame($unknown['raw'], $response['raw']);
		self::assertStringNotContainsString('application/json', $response['headers']['content-type'] ?? '');
		self::assertNull(json_decode($response['raw'], true), 'The answer is JSON: ' . $response['raw']);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function endpointPaths(): array {
		return [
			'endpoint' => ['/api'],
			'endpoint with slash' => ['/api/'],
			'endpoint with query' => ['/api?x=1'],
			'endpoint with slash and query' => ['/api/?x=1'],
		];
	}

	/**
	 * The endpoint without a route is answered by AppApi itself.
	 */
	#[DataProvider('endpointPaths')]
	public function testEndpointIsStillAnsweredByTheApi(string $path): void {
		$response = $this->siteRequest($path, true);

		self::assertSame(400, $response['status'], $response['raw']);
		self::assertStringContainsString('application/json', $response['headers']['content-type'] ?? '');
		$json = json_decode($response['raw'], true);
		self::assertIsArray($json, $response['raw']);
		self::assertSame('bad_request_exception', $json['errorcode'] ?? null);
		self::assertSame('No endpoint defined', $json['error'] ?? null);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function routePaths(): array {
		return [
			'route' => ['/api/tpage/test-fixture-content'],
			'route with slash' => ['/api/tpage/test-fixture-content/'],
			'route with query' => ['/api/tpage/test-fixture-content?x=1'],
		];
	}

	#[DataProvider('routePaths')]
	public function testRouteBelowTheEndpointStillAnswers(string $path): void {
		$response = $this->siteRequest($path, true);

		self::assertSame(200, $response['status'], $response['raw']);
		$json = json_decode($response['raw'], true);
		self::assertIsArray($json, $response['raw']);
		self::assertSame(Fixtures::page(Fixtures::CONTENT_PATH)->id, $json['id'] ?? null);
	}

	/**
	 * Only the endpoint at the beginning is removed from the route: a later
	 * "/api/" segment belongs to the page path.
	 */
	public function testEndpointIsOnlyRemovedFromTheBeginningOfTheRoute(): void {
		$response = $this->siteRequest('/api/tpage/api/test-fixture-content', true);

		self::assertSame(404, $response['status'], $response['raw']);
		self::assertSame('not_found_exception', json_decode($response['raw'], true)['errorcode'] ?? null, $response['raw']);
	}

	/**
	 * Request below the site root, with the API key when $withApiKey is set.
	 *
	 * @return array{status: int, raw: string, headers: array<string, string>}
	 */
	private function siteRequest(string $path, bool $withApiKey = false): array {
		$apiUrl = getenv('MF_TEST_API_URL') ?: 'https://127.0.0.1:8001/api/';
		$siteUrl = preg_replace('#/api/?$#', '', rtrim($apiUrl, '/'));

		$responseHeaders = [];
		$ch = curl_init($siteUrl . $path);
		curl_setopt_array($ch, [
			CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseHeaders): int {
				$parts = explode(':', $line, 2);
				if (count($parts) === 2) {
					$responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
				}

				return strlen($line);
			},
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => $withApiKey ? ['X-API-KEY: ' . self::apiKey()] : [],
		]);
		$raw = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return ['status' => $status, 'raw' => (string) $raw, 'headers' => $responseHeaders];
	}
}
