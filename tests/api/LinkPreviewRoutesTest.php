<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\Page;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * URL `link-preview/?path=…` (outside the API endpoint, no API key): HTML for
 * link preview services, status, headers and escaping. Creates its own pages
 * and removes them afterwards.
 */
final class LinkPreviewRoutesTest extends ApiTestCase {
	/** @var array<string, Page>|null */
	private static ?array $pages = null;

	protected function setUp(): void {
		parent::setUp();

		if (self::$pages === null) {
			self::$pages = Fixtures::createLinkPreviewTree();
		}
	}

	public static function tearDownAfterClass(): void {
		if (self::$pages !== null) {
			Fixtures::deleteLinkPreviewTree();
			self::$pages = null;
		}
		parent::tearDownAfterClass();
	}

	public function testPublicPageGetsItsOwnTagsAndTheContractHeaders(): void {
		$response = $this->previewRequest(Fixtures::CONTENT_PATH);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertSame('text/html; charset=utf-8', $response['headers']['content-type'] ?? null);
		self::assertSame('private, max-age=600', $response['headers']['cache-control'] ?? null);
		self::assertSame('User-Agent', $response['headers']['vary'] ?? null);
		self::assertArrayNotHasKey('set-cookie', $response['headers']);
		self::assertSame('noindex', $response['headers']['x-robots-tag'] ?? null);
		self::assertArrayNotHasKey('pragma', $response['headers']);
		self::assertArrayNotHasKey('expires', $response['headers']);
		self::assertMatchesRegularExpression('#^<!doctype html>\s*<html lang="de">#', $response['raw']);
		self::assertMatchesRegularExpression('#<title>Test Fixture Content[^<]*</title>#', $response['raw']);
		self::assertMatchesRegularExpression('#<link rel="canonical" href="https://[^/"]+' . preg_quote(Fixtures::CONTENT_PATH, '#') . '">#', $response['raw']);
		self::assertStringNotContainsStringIgnoringCase('<script', $response['raw']);
	}

	public function testPathFormsOfTheRewriteRuleFindThePage(): void {
		foreach (['/test-fixture-content', '/test-fixture-content/?utm_source=x&b=1', '/Test-Fixture-Content/'] as $path) {
			$response = $this->previewRequest($path);

			self::assertSame(200, $response['status'], $path);
			self::assertStringContainsString('<title>Test Fixture Content', $response['raw'], $path);
		}
	}

	public function testPagesAGuestMayNotSeeAreNotFoundWithTheSiteDefaults(): void {
		$siteName = htmlspecialchars(trim((string) wire('pages')->get('template.name=configuration, include=all')->seo_title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$cases = [
			Fixtures::UNPUBLISHED_PATH => Fixtures::page(Fixtures::UNPUBLISHED_PATH)->title,
			Fixtures::PORTRAIT_1_PATH => Fixtures::page(Fixtures::PORTRAIT_1_PATH)->title,
		];
		foreach (['not_released', 'permissions', 'password', 'password_child', 'role_child'] as $key) {
			$cases[self::$pages[$key]->path] = self::$pages[$key]->title;
		}

		foreach ($cases as $path => $title) {
			$response = $this->previewRequest($path);

			self::assertSame(404, $response['status'], $path);
			self::assertSame('text/html; charset=utf-8', $response['headers']['content-type'] ?? null, $path);
			self::assertSame('noindex', $response['headers']['x-robots-tag'] ?? null, $path);
			self::assertSame('private, max-age=600', $response['headers']['cache-control'] ?? null, $path);
			self::assertSame('User-Agent', $response['headers']['vary'] ?? null, $path);
			self::assertArrayNotHasKey('set-cookie', $response['headers'], $path);
			self::assertStringContainsString('<title>' . $siteName . '</title>', $response['raw'], $path);
			self::assertStringNotContainsString($title, $response['raw'], $path);
			self::assertStringNotContainsString($path, $response['raw'], $path);
		}
	}

	public function testHiddenPageGetsItsOwnTags(): void {
		$path = self::$pages['hidden']->path;

		$response = $this->previewRequest($path);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertStringContainsString('<title>Test Link Preview Hidden', $response['raw']);
		self::assertMatchesRegularExpression('#<link rel="canonical" href="https://[^/"]+' . preg_quote($path, '#') . '">#', $response['raw']);
	}

	public function testScriptInThePathIsNotFoundAndNotEchoed(): void {
		$response = $this->previewRequest('/<script>alert(1)</script>/');

		self::assertSame(404, $response['status'], $response['raw']);
		self::assertStringNotContainsStringIgnoringCase('<script', $response['raw']);
		self::assertStringNotContainsString('alert(1)', $response['raw']);
	}

	public function testPathWithMoreSegmentsThanTheDatabaseJoinsIsNotFound(): void {
		$response = $this->previewRequest(str_repeat('/a', 62) . '/');

		self::assertSame(404, $response['status'], $response['raw']);
		self::assertStringContainsString('<!doctype html>', $response['raw']);
	}

	public function testMissingPathIsNotFound(): void {
		$response = $this->rawRequest('link-preview/');

		self::assertSame(404, $response['status'], $response['raw']);
	}

	public function testTitleAndDescriptionAreEscaped(): void {
		$response = $this->previewRequest(self::$pages['special']->path);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertStringContainsString('<title>Zitat &quot;A&quot; &amp; B &lt; C', $response['raw']);
		self::assertStringContainsString('<meta property="og:title" content="Zitat &quot;A&quot; &amp; B &lt; C', $response['raw']);
		self::assertStringContainsString('<meta name="description" content="Text &quot;x&quot; &amp; y &lt; z">', $response['raw']);
		self::assertStringNotContainsString(Fixtures::LINK_PREVIEW_SPECIAL_TITLE, $response['raw']);
	}

	/**
	 * Request as the rewrite rule of the web server sends it: the frontend
	 * path in the query parameter `path`.
	 *
	 * @return array{status: int, raw: string, headers: array<string, string>}
	 */
	private function previewRequest(string $path): array {
		return $this->rawRequest('link-preview/?path=' . rawurlencode($path));
	}

	/**
	 * GET below the site root (outside the API endpoint), without API key and
	 * without cookies.
	 *
	 * @return array{status: int, raw: string, headers: array<string, string>}
	 */
	private function rawRequest(string $relativeUrl): array {
		$apiUrl = getenv('MF_TEST_API_URL') ?: 'https://127.0.0.1:8001/api/';
		$siteUrl = preg_replace('#api/?$#', '', rtrim($apiUrl, '/') . '/');

		$headers = [];
		$ch = curl_init($siteUrl . $relativeUrl);
		curl_setopt_array($ch, [
			CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
				$parts = explode(':', $line, 2);
				if (count($parts) === 2) {
					$headers[strtolower(trim($parts[0]))] = trim($parts[1]);
				}

				return strlen($line);
			},
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

		return ['status' => $status, 'raw' => (string) $raw, 'headers' => $headers];
	}
}
