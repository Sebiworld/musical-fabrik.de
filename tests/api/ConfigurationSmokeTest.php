<?php

declare(strict_types=1);
namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * Confirms the test API server is reachable and serves the public
 * configuration route.
 */
final class ConfigurationSmokeTest extends ApiTestCase {
	public function testConfigurationRouteRespondsWithOk(): void {
		$response = $this->apiRequest('GET', 'configuration');

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertIsArray($response['json']);
	}
}
