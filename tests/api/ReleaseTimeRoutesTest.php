<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\Page;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

/**
 * A project in preparation (release time in the future): guests get neither
 * the project nor its pages, its performance or a link preview of them, and
 * no `seo` object or image of it. Creates its own pages and removes them
 * afterwards.
 */
final class ReleaseTimeRoutesTest extends ApiTestCase {
	/** @var array<string, Page>|null */
	private static ?array $pages = null;

	protected function setUp(): void {
		parent::setUp();

		if (self::$pages === null) {
			self::$pages = Fixtures::createReleaseTree();
		}
	}

	public static function tearDownAfterClass(): void {
		if (self::$pages !== null) {
			Fixtures::deleteReleaseTree();
			self::$pages = null;
		}
		parent::tearDownAfterClass();
	}

	public function testRoutesAnswerTheUnreleasedTreeLikeMissingPages(): void {
		$p = self::$pages;
		$routes = [
			'projects/' . $p['project']->id,
			'performances/' . $p['period']->id,
		];
		foreach (['project', 'info', 'article', 'gallery'] as $key) {
			$routes[] = 'tpage/' . trim($p[$key]->path, '/');
		}

		foreach ($routes as $route) {
			$response = $this->apiRequest('GET', $route);

			self::assertSame(404, $response['status'], $route . ': ' . $response['raw']);
			self::assertStringNotContainsString('Test Fixture Release', $response['raw'], $route);
			self::assertStringNotContainsString(pathinfo(Fixtures::RELEASE_PROJECT_IMAGE, PATHINFO_FILENAME), $response['raw'], $route);
		}
	}

	public function testReleasedControlPageIsDelivered(): void {
		$response = $this->apiRequest('GET', 'tpage/' . trim(self::$pages['control']->path, '/'));

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertSame('Test Fixture Release Control', $response['json']['title'] ?? null);
	}

	public function testLinkPreviewOfTheUnreleasedTreeIsNotFound(): void {
		$p = self::$pages;
		$paths = [$p['project']->path . 'vorstellungen/' . $p['period']->id . '/'];
		foreach (['project', 'info', 'article', 'gallery'] as $key) {
			$paths[] = $p[$key]->path;
		}

		foreach ($paths as $path) {
			$response = $this->previewRequest($path);

			self::assertSame(404, $response['status'], $path);
			self::assertStringNotContainsString('Test Fixture Release', $response['raw'], $path);
			self::assertStringNotContainsString(pathinfo(Fixtures::RELEASE_PROJECT_IMAGE, PATHINFO_FILENAME), $response['raw'], $path);
		}

		$control = $this->previewRequest($p['control']->path);
		self::assertSame(200, $control['status'], $control['raw']);
		self::assertStringContainsString('<title>Test Fixture Release Control', $control['raw']);
	}

	/**
	 * @return array{status: int, raw: string}
	 */
	private function previewRequest(string $path): array {
		$apiUrl = getenv('MF_TEST_API_URL') ?: 'https://127.0.0.1:8001/api/';
		$siteUrl = preg_replace('#api/?$#', '', rtrim($apiUrl, '/') . '/');

		$ch = curl_init($siteUrl . 'link-preview/?path=' . rawurlencode($path));
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_COOKIE => null,
		]);
		$raw = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return ['status' => $status, 'raw' => (string) $raw];
	}
}
