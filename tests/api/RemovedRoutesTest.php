<?php

declare(strict_types=1);
namespace Tests\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ApiTestCase;

/**
 * Routes that were removed must not be reachable anymore. A plain 404 is not
 * enough: handlers that crash can answer with 404 as well, so the router's
 * "Route not found" message is checked too.
 */
final class RemovedRoutesTest extends ApiTestCase {
	/**
	 * @return array<string, array{string, string}>
	 */
	public static function removedRoutes(): array {
		return [
			'registration' => ['POST', 'auth/registration'],
			'registration confirm' => ['POST', 'auth/registration_confirm'],
			'error test' => ['GET', 'test'],
		];
	}

	#[DataProvider('removedRoutes')]
	public function testRemovedRouteIsNotFound(string $method, string $path): void {
		$response = $this->apiRequest($method, $path);

		self::assertSame(404, $response['status'], $response['raw']);
		self::assertSame('Route not found', $response['json']['error'] ?? null, $response['raw']);
	}

	public function testRegistrationIsReportedAsDisabled(): void {
		$response = $this->apiRequest('GET', 'configuration');

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertArrayHasKey('activate_registration', $response['json']);
		self::assertFalse($response['json']['activate_registration']);
	}
}
