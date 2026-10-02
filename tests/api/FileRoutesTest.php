<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\Page;
use Tests\Support\AccessTokens;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

/**
 * Route `file/{id}?file=…` (module AppApiFile): caching headers, conditional
 * requests, image sizes and hidden pages, requested without a session.
 * Creates its own pages with generated images and removes them afterwards.
 */
final class FileRoutesTest extends ApiTestCase {
	private const MAX_DIMENSION = 4096;

	/** @var array<string, Page>|null */
	private static ?array $pages = null;

	protected function setUp(): void {
		parent::setUp();

		if (self::$pages === null) {
			self::$pages = Fixtures::createFileTree();
		}
	}

	public static function tearDownAfterClass(): void {
		AccessTokens::deleteAll();
		if (self::$pages !== null) {
			Fixtures::deleteFileTree();
			self::$pages = null;
		}
		parent::tearDownAfterClass();
	}

	public function testWithVersionTheImageIsCachedForAYear(): void {
		$plain = $this->request(Fixtures::SMALL_IMAGE);
		$versioned = $this->request(Fixtures::SMALL_IMAGE, '&v=abc123');

		self::assertSame(200, $versioned['status']);
		self::assertSame('public, max-age=31536000, immutable', $versioned['headers']['cache-control'] ?? null);
		self::assertArrayNotHasKey('expires', $versioned['headers']);
		self::assertArrayNotHasKey('pragma', $versioned['headers']);
		self::assertSame(sha1($plain['raw']), sha1($versioned['raw']), 'The version parameter must not change the response body.');
	}

	public function testWithoutVersionTheImageMustBeRevalidated(): void {
		$response = $this->request(Fixtures::SMALL_IMAGE);

		self::assertSame(200, $response['status']);
		self::assertSame('no-cache', $response['headers']['cache-control'] ?? null);
		self::assertNotEmpty($response['headers']['etag'] ?? null);
		self::assertArrayNotHasKey('expires', $response['headers']);
		self::assertArrayNotHasKey('pragma', $response['headers']);
	}

	public function testMatchingEtagAnswersWith304(): void {
		$first = $this->request(Fixtures::SMALL_IMAGE);
		self::assertSame(200, $first['status']);
		$etag = $first['headers']['etag'] ?? '';
		self::assertNotSame('', $etag);

		$second = $this->request(Fixtures::SMALL_IMAGE, '', ['If-None-Match: ' . $etag]);

		self::assertSame(304, $second['status']);
		self::assertSame('', $second['raw']);
		self::assertSame($etag, $second['headers']['etag'] ?? null);

		$other = $this->request(Fixtures::SMALL_IMAGE, '', ['If-None-Match: "not-the-current-etag"']);
		self::assertSame(200, $other['status']);
		self::assertNotSame('', $other['raw']);
	}

	public function testWidthAboveTheOriginalReturnsTheOriginalSize(): void {
		[$width, $height] = Fixtures::SMALL_IMAGE_SIZE;

		$wider = $this->request(Fixtures::SMALL_IMAGE, '&width=' . ($width * 2));
		self::assertSame(200, $wider['status']);
		self::assertSame([$width, $height], self::imageSize($wider['raw']));

		$both = $this->request(Fixtures::SMALL_IMAGE, '&width=' . ($width * 2) . '&height=' . ($height * 2));
		self::assertSame(200, $both['status']);
		self::assertSame([$width, $height], self::imageSize($both['raw']));
	}

	public function testWidthAboveTheLimitIsCappedAtTheLimit(): void {
		[$width, $height] = Fixtures::LARGE_IMAGE_SIZE;
		self::assertGreaterThan(self::MAX_DIMENSION, $width, 'The large test image must be wider than the limit.');

		$response = $this->request(Fixtures::LARGE_IMAGE, '&width=' . ($width - 100));

		self::assertSame(200, $response['status']);
		self::assertSame([self::MAX_DIMENSION, (int) round($height * self::MAX_DIMENSION / $width)], self::imageSize($response['raw']));

		$original = $this->request(Fixtures::LARGE_IMAGE);
		self::assertSame([$width, $height], self::imageSize($original['raw']), 'Without a size the original stays reachable.');
	}

	public function testImageOfUnpublishedPageIsNotFoundLikeAnUnknownId(): void {
		$unpublished = self::$pages['unpublished'];
		self::assertTrue($unpublished->isUnpublished(), 'The test page is expected to be unpublished.');

		$hidden = $this->apiRequest('GET', 'file/' . $unpublished->id . '?file=' . Fixtures::SMALL_IMAGE);
		$unknown = $this->apiRequest('GET', 'file/999999999?file=' . Fixtures::SMALL_IMAGE);

		self::assertSame(404, $hidden['status'], $hidden['raw']);
		self::assertSame('not_found_exception', $hidden['json']['errorcode'] ?? null);
		self::assertSame('Not Found.', $hidden['json']['error'] ?? null);
		self::assertSameErrorResponse($unknown, $hidden);
	}

	public function testEtagOfAHiddenImageGivesNotFoundInsteadOf304(): void {
		$unpublished = self::$pages['unpublished'];
		$authorized = $this->apiRequest('GET', 'file/' . $unpublished->id . '?file=' . Fixtures::SMALL_IMAGE, [AccessTokens::authorizationHeader(AccessTokens::superuserId())]);
		self::assertSame(200, $authorized['status']);
		$etag = $authorized['headers']['etag'] ?? '';
		self::assertNotSame('', $etag);

		foreach ([$etag, '*'] as $ifNoneMatch) {
			$hidden = $this->apiRequest('GET', 'file/' . $unpublished->id . '?file=' . Fixtures::SMALL_IMAGE, ['If-None-Match: ' . $ifNoneMatch]);
			$unknown = $this->apiRequest('GET', 'file/999999999?file=' . Fixtures::SMALL_IMAGE, ['If-None-Match: ' . $ifNoneMatch]);

			self::assertSame(404, $hidden['status'], $ifNoneMatch . ': ' . $hidden['raw']);
			self::assertSameErrorResponse($unknown, $hidden, $ifNoneMatch);
		}
	}

	public function testWeakEtagAndEtagListAnswerWith304(): void {
		$etag = $this->request(Fixtures::SMALL_IMAGE)['headers']['etag'] ?? '';
		self::assertNotSame('', $etag);

		$weak = $this->request(Fixtures::SMALL_IMAGE, '', ['If-None-Match: W/' . $etag]);
		self::assertSame(304, $weak['status'], 'weak ETag');
		self::assertSame('', $weak['raw']);

		$list = $this->request(Fixtures::SMALL_IMAGE, '', ['If-None-Match: "other-etag", ' . $etag . ', "third"']);
		self::assertSame(304, $list['status'], 'ETag list');
		self::assertSame('', $list['raw']);
	}

	public function testMaxwidthAboveTheLimitIsCappedAtTheLimit(): void {
		[$width, $height] = Fixtures::LARGE_IMAGE_SIZE;

		$response = $this->request(Fixtures::LARGE_IMAGE, '&maxwidth=99999');

		self::assertSame(200, $response['status']);
		self::assertSame([self::MAX_DIMENSION, (int) round($height * self::MAX_DIMENSION / $width)], self::imageSize($response['raw']));
	}

	public function testNonPublicImageIsOnlyCachedPrivatelyForAuthorizedUsers(): void {
		$unpublished = self::$pages['unpublished'];
		$path = 'file/' . $unpublished->id . '?file=' . Fixtures::SMALL_IMAGE;
		$authorization = AccessTokens::authorizationHeader(AccessTokens::superuserId());

		foreach (['' => 'without v', '&v=abc123' => 'with v'] as $query => $label) {
			$response = $this->apiRequest('GET', $path . $query, [$authorization]);

			self::assertSame(200, $response['status'], $label . ': ' . substr($response['raw'], 0, 300));
			self::assertSame([600, 400], self::imageSize($response['raw']), $label);
			self::assertSame('private, no-cache', $response['headers']['cache-control'] ?? null, $label);
			self::assertArrayNotHasKey('expires', $response['headers'], $label);
			self::assertArrayNotHasKey('pragma', $response['headers'], $label);

			$revalidated = $this->apiRequest('GET', $path . $query, [$authorization, 'If-None-Match: ' . ($response['headers']['etag'] ?? '')]);
			self::assertSame(304, $revalidated['status'], $label);
			self::assertSame('private, no-cache', $revalidated['headers']['cache-control'] ?? null, $label);
		}
	}

	public function testPublicImageIsCachedPrivatelyForLoggedInUsers(): void {
		$authorization = AccessTokens::authorizationHeader(AccessTokens::superuserId());

		$versioned = $this->request(Fixtures::SMALL_IMAGE, '&v=abc123', [$authorization]);
		self::assertSame(200, $versioned['status']);
		self::assertSame('private, max-age=31536000, immutable', $versioned['headers']['cache-control'] ?? null);

		$plain = $this->request(Fixtures::SMALL_IMAGE, '', [$authorization]);
		self::assertSame(200, $plain['status']);
		self::assertSame('private, no-cache', $plain['headers']['cache-control'] ?? null);

		$revalidated = $this->request(Fixtures::SMALL_IMAGE, '&v=abc123', [$authorization, 'If-None-Match: ' . ($versioned['headers']['etag'] ?? '')]);
		self::assertSame(304, $revalidated['status']);
		self::assertSame('private, max-age=31536000, immutable', $revalidated['headers']['cache-control'] ?? null);
	}

	public function testImageOfAPageWithFutureReleaseTimeIsNeverCachedPublicly(): void {
		$page = self::$pages['not_released'];
		self::assertFalse($page->isUnpublished(), 'The test page is expected to be published.');
		$path = 'file/' . $page->id . '?file=' . Fixtures::SMALL_IMAGE . '&v=abc123';

		$guest = $this->apiRequest('GET', $path);
		self::assertSame(404, $guest['status'], 'Guests must not see the page before its release time.');

		$authorized = $this->apiRequest('GET', $path, [AccessTokens::authorizationHeader(AccessTokens::superuserId())]);
		self::assertSame(200, $authorized['status'], substr($authorized['raw'], 0, 300));
		self::assertStringNotContainsString('public', $authorized['headers']['cache-control'] ?? '');
		self::assertStringStartsWith('private', $authorized['headers']['cache-control'] ?? '');
	}

	/**
	 * A client must not be able to tell a hidden page from an unknown one:
	 * same status, same headers (apart from `date` and the session id) and
	 * the same body. `devmessage` is only sent in debug mode and names the
	 * throwing line, so it is left out.
	 */
	private static function assertSameErrorResponse(array $expected, array $actual, string $message = ''): void {
		self::assertSame($expected['status'], $actual['status'], $message . ' (status)');
		self::assertSame(self::withoutDevMessage($expected['json']), self::withoutDevMessage($actual['json']), $message . ' (body)');
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

	/**
	 * @return array{status: int, json: mixed, raw: string, headers: array<string, string>}
	 */
	private function request(string $basename, string $query = '', array $headers = []): array {
		return $this->apiRequest('GET', 'file/' . self::$pages['page']->id . '?file=' . $basename . $query, $headers);
	}

	/**
	 * @return array{0: int, 1: int}|null
	 */
	private static function imageSize(string $body): ?array {
		$size = getimagesizefromstring($body);

		return $size === false ? null : [$size[0], $size[1]];
	}

	/**
	 * `devmessage` is only sent in debug mode and names the throwing line.
	 */
	private static function withoutDevMessage($json): array {
		self::assertIsArray($json);
		unset($json['devmessage']);

		return $json;
	}
}
