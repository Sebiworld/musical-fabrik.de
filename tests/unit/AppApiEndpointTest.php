<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ProcessWire\AppApi;
use ProcessWire\WireHooks;

use function ProcessWire\wire;

/**
 * AppApi treats a URL as an API request only if its path is the endpoint
 * itself or starts with "<endpoint>/": "/apifoo/" is no API request. The
 * endpoint is only removed from the beginning of a route URL, and the URL
 * hook of the module matches the same paths.
 *
 * The endpoint is changed on the module instance of this process only and
 * never saved to the module configuration.
 */
final class AppApiEndpointTest extends TestCase {
	private ?string $savedEndpoint = null;

	protected function setUp(): void {
		$this->savedEndpoint = (string) $this->appApi()->endpoint;
	}

	protected function tearDown(): void {
		$this->appApi()->endpoint = $this->savedEndpoint;
	}

	/**
	 * @return array<string, array{string, string, bool}>
	 */
	public static function urls(): array {
		return [
			'endpoint' => ['api', '/api', true],
			'endpoint with slash' => ['api', '/api/', true],
			'route below the endpoint' => ['api', '/api/tpage/projekte/', true],
			'endpoint with query' => ['api', '/api?x=1', true],
			'endpoint with slash and query' => ['api', '/api/?x=1', true],
			'longer first segment' => ['api', '/apiXYZ/', false],
			'longer first segment without slash' => ['api', '/apiXYZ', false],
			'longer first segment with dash' => ['api', '/api-docs/', false],
			'longer first segment with query' => ['api', '/apifoo?x=1', false],
			'endpoint as a later segment' => ['api', '/page/api/', false],
			'root' => ['api', '/', false],
			'endpoint with sub path' => ['api/v1', '/api/v1/tpage/', true],
			'endpoint with sub path, exact' => ['api/v1', '/api/v1', true],
			'endpoint with sub path, longer last segment' => ['api/v1', '/api/v12/', false],
			'endpoint with sub path, only first segment' => ['api/v1', '/api/', false],
			'endpoint configured with slashes' => ['/api/', '/api/tpage/', true],
			'endpoint configured with slashes, longer segment' => ['/api/', '/apiXYZ/', false],
		];
	}

	#[DataProvider('urls')]
	public function testOnlyTheWholeEndpointSegmentIsAnApiUrl(string $endpoint, string $url, bool $expected): void {
		$this->appApi()->endpoint = $endpoint;

		$this->assertSame($expected, $this->appApi()->isEndpointUrl($url));
	}

	/**
	 * @return array<string, array{string, string, string}>
	 */
	public static function routeUrls(): array {
		return [
			'route' => ['api', '/api/tpage/projekte/', '/tpage/projekte/'],
			'endpoint only' => ['api', '/api', '/'],
			'endpoint with slash' => ['api', '/api/', '/'],
			'endpoint as a later segment stays' => ['api', '/api/tpage/api/page/', '/tpage/api/page/'],
			'segment starting with the endpoint stays' => ['api', '/api/tpage/apifoo/', '/tpage/apifoo/'],
			'other url stays' => ['api', '/apiXYZ/', '/apiXYZ/'],
			'endpoint with sub path' => ['api/v1', '/api/v1/tpage/', '/tpage/'],
		];
	}

	#[DataProvider('routeUrls')]
	public function testEndpointIsOnlyRemovedFromTheBeginning(string $endpoint, string $url, string $expected): void {
		$this->appApi()->endpoint = $endpoint;

		$this->assertSame($expected, $this->appApi()->removeEndpointFromUrl($url));
	}

	/**
	 * @return array<string, array{string, string, bool}>
	 */
	public static function urlHookPaths(): array {
		return [
			'endpoint' => ['api', '/api', true],
			'endpoint with slash' => ['api', '/api/', true],
			'route below the endpoint' => ['api', '/api/x/y', true],
			'route below the endpoint with slash' => ['api', '/api/x/y/', true],
			'longer first segment' => ['api', '/apifoo', false],
			'longer first segment with slash' => ['api', '/apifoo/', false],
			'longer first segment with dash' => ['api', '/api-x/', false],
			'endpoint as a later segment' => ['api', '/x/api/', false],
			'sub path endpoint' => ['api/v1', '/api/v1', true],
			'sub path endpoint with slash' => ['api/v1', '/api/v1/', true],
			'route below the sub path endpoint' => ['api/v1', '/api/v1/x/y', true],
			'sub path endpoint, longer last segment' => ['api/v1', '/api/v1foo', false],
			'sub path endpoint, longer last segment with slash' => ['api/v1', '/api/v1foo/', false],
			'sub path endpoint, only first segment' => ['api/v1', '/api/', false],
			'sub path endpoint as a later segment' => ['api/v1', '/x/api/v1/', false],
		];
	}

	/**
	 * The URL hook pattern is run through the path hook matching of
	 * ProcessWire itself (WireHooks), without a request. The hook is removed
	 * again afterwards.
	 */
	#[DataProvider('urlHookPaths')]
	public function testUrlHookMatchesOnlyTheEndpointAndPathsBelowIt(string $endpoint, string $path, bool $expected): void {
		$this->appApi()->endpoint = $endpoint;
		$pattern = $this->callProtected($this->appApi(), 'getUrlHookPattern');

		$hooks = wire()->wire('hooks');
		$this->assertInstanceOf(WireHooks::class, $hooks);
		$allowed = $hooks->allowPathHooks();
		$hooks->allowPathHooks(true);
		$hookId = wire()->addHook($pattern, function (): void {});
		try {
			$arguments = [$path];
			$matched = $this->callProtected($hooks, 'allowRunPathHook', $hookId, $arguments);
		} finally {
			wire()->removeHook($hookId);
			$hooks->allowPathHooks($allowed);
		}

		$this->assertSame($expected, $matched, 'pattern ' . $pattern);
		if ($matched) {
			// No capture group: the hook gets the path only.
			$this->assertSame([0, 'path'], array_keys($arguments));
		}
	}

	public function testEmptyEndpointFallsBackToApi(): void {
		$this->appApi()->endpoint = '';

		$this->assertSame('api', $this->appApi()->getEndpointPath());
		$this->assertTrue($this->appApi()->isEndpointUrl('/api/'));
	}

	/**
	 * Calls a protected method; arguments are passed by reference.
	 */
	private function callProtected(object $object, string $method, mixed &...$arguments): mixed {
		$reflection = new \ReflectionMethod($object, $method);
		$reflection->setAccessible(true);

		return $reflection->invokeArgs($object, $arguments);
	}

	private function appApi(): AppApi {
		$appApi = wire('modules')->get('AppApi');
		$this->assertInstanceOf(AppApi::class, $appApi);

		return $appApi;
	}
}
