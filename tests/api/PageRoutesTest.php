<?php

declare(strict_types=1);
namespace Tests\Api;

use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

/**
 * Route `tpage/{path}`: page requests by path, made without a session.
 */
final class PageRoutesTest extends ApiTestCase {
	public function testContentPageHasTheExpectedShape(): void {
		$page = Fixtures::page(Fixtures::CONTENT_PATH);

		$response = $this->apiRequest('GET', 'tpage/test-fixture-content');

		self::assertSame(200, $response['status'], $response['raw']);
		$json = $response['json'];
		self::assertSame($page->id, $json['id']);
		self::assertSame('Test Fixture Content', $json['title']);
		self::assertSame('default_page', $json['template']['name']);
		self::assertNotEmpty($json['hash']);

		self::assertNotEmpty($json['contents']);
		self::assertSame('text', $json['contents'][0]['type']);

		self::assertNotEmpty($json['sections']);
		self::assertSame('Test Fixture Section', $json['sections'][0]['title']);
		self::assertNotEmpty($json['sections'][0]['contents']);
		self::assertSame('text', $json['sections'][0]['contents'][0]['type']);
	}

	public function testUnchangedHashAnswersWith204(): void {
		$first = $this->apiRequest('GET', 'tpage/test-fixture-content');
		self::assertSame(200, $first['status'], $first['raw']);

		$second = $this->apiRequest('GET', 'tpage/test-fixture-content?hash=' . $first['json']['hash']);

		self::assertSame(204, $second['status']);
	}

	public function testUnknownPathIsNotFound(): void {
		$response = $this->apiRequest('GET', 'tpage/test-fixture-does-not-exist');

		self::assertSame(404, $response['status'], $response['raw']);
		self::assertSame('not_found_exception', $response['json']['errorcode']);
	}

	public function testUnpublishedPageIsNotFoundForGuestsLikeAnUnknownPath(): void {
		$page = Fixtures::page(Fixtures::UNPUBLISHED_PATH);
		self::assertTrue($page->isUnpublished(), 'The seed page is expected to be unpublished.');

		$hidden = $this->apiRequest('GET', 'tpage/test-fixture-unpublished');
		$unknown = $this->apiRequest('GET', 'tpage/test-fixture-does-not-exist');

		self::assertSame(404, $hidden['status'], $hidden['raw']);
		self::assertSame('not_found_exception', $hidden['json']['errorcode']);
		self::assertSame('Not Found.', $hidden['json']['error']);
		self::assertArrayNotHasKey('title', $hidden['json']);
		self::assertIndistinguishable($unknown, $hidden);
	}

	public function testUnpublishedPageByIdIsNotFoundForGuestsLikeAnUnknownId(): void {
		$page = Fixtures::page(Fixtures::UNPUBLISHED_PATH);

		$hidden = $this->apiRequest('GET', 'tpage/' . $page->id);
		$unknown = $this->apiRequest('GET', 'tpage/2147483000');

		self::assertSame(404, $hidden['status'], $hidden['raw']);
		self::assertIndistinguishable($unknown, $hidden);
	}
}
