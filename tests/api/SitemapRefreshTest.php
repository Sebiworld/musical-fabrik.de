<?php

declare(strict_types=1);
namespace Tests\Api;

use Tests\Support\ApiTestCase;

use function ProcessWire\wire;

/**
 * The web server rewrites an outdated sitemap file at the end of the next
 * request, also of an API request, and delivers the new one at /sitemap.xml.
 * Marks the sitemap file of the backend as outdated (shared with the
 * development server); the test server writes it again from the test
 * database. The only test that touches the real file.
 */
final class SitemapRefreshTest extends ApiTestCase {
	public function testAnApiRequestRewritesTheOutdatedSitemap(): void {
		$sitemap = wire('modules')->get('Twack')->getService('SitemapService');
		// The test process uses a sitemap path of its own (tests/bootstrap.php);
		// this test needs the real file, which the test server writes.
		$testPath = $sitemap->getPath();
		$sitemap->setPath(null);
		try {
			$this->checkThatAnApiRequestRewritesTheOutdatedSitemap($sitemap);
		} finally {
			$sitemap->setPath($testPath);
		}
	}

	private function checkThatAnApiRequestRewritesTheOutdatedSitemap(object $sitemap): void {
		$path = $sitemap->getPath();
		self::assertNotSame('', $path, 'SeoMaestro is expected to manage a sitemap file.');
		if (!is_file($path)) {
			$this->apiRequest('GET', 'tpage/');
			$this->waitForFile($path, 0);
		}
		self::assertFileExists($path);
		$sitemap->markOutdated();
		clearstatcache(true, $path);
		self::assertSame(0, filemtime($path), 'The file is expected to be marked as outdated.');

		$started = time();
		$response = $this->apiRequest('GET', 'tpage/');
		self::assertSame(200, $response['status'], $response['raw']);

		self::assertGreaterThanOrEqual($started - 1, $this->waitForFile($path, 0), 'The request is expected to rewrite the sitemap file.');
		self::assertFalse($sitemap->isOutdated());

		$xml = $this->fetchSitemap();
		self::assertSame(200, $xml['status'], $xml['raw']);
		self::assertStringStartsWith('<?xml', $xml['raw']);
		self::assertStringContainsString('<urlset', $xml['raw']);
	}

	/**
	 * Waits up to 15 seconds until the modification time of the file is
	 * newer than the given one and returns it (the given one on timeout).
	 */
	private function waitForFile(string $path, int $notAfter): int {
		$deadline = microtime(true) + 15;
		do {
			clearstatcache(true, $path);
			$mtime = is_file($path) ? (int) filemtime($path) : $notAfter;
			if ($mtime > $notAfter) {
				return $mtime;
			}
			usleep(200000);
		} while (microtime(true) < $deadline);

		return $mtime;
	}

	/**
	 * @return array{status: int, raw: string}
	 */
	private function fetchSitemap(): array {
		$apiUrl = getenv('MF_TEST_API_URL') ?: 'https://127.0.0.1:8001/api/';
		$siteUrl = preg_replace('#/api/?$#', '', rtrim($apiUrl, '/'));

		$ch = curl_init($siteUrl . '/sitemap.xml');
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 30,
		]);
		$raw = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return ['status' => $status, 'raw' => (string) $raw];
	}
}
