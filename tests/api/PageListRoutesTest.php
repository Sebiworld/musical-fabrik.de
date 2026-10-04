<?php

declare(strict_types=1);
namespace Tests\Api;

use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

/**
 * Route `page-list/items/{id}`, requested without a session.
 */
final class PageListRoutesTest extends ApiTestCase {
	public function testItemsOfAPublishedPageAreDelivered(): void {
		$page = Fixtures::page(Fixtures::CONTENT_PATH);

		$response = $this->apiRequest('GET', 'page-list/items/' . $page->id);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertIsArray($response['json']);
	}

	public function testItemsOfAnUnpublishedPageAreNotFoundLikeAnUnknownId(): void {
		$unpublished = Fixtures::page(Fixtures::UNPUBLISHED_PATH);
		self::assertTrue($unpublished->isUnpublished(), 'The seed page is expected to be unpublished.');

		$hidden = $this->apiRequest('GET', 'page-list/items/' . $unpublished->id);
		$unknown = $this->apiRequest('GET', 'page-list/items/2147483000');

		self::assertSame(404, $hidden['status'], $hidden['raw']);
		self::assertSame('not_found_exception', $hidden['json']['errorcode']);
		self::assertSame('Not Found.', $hidden['json']['error']);
		self::assertIndistinguishable($unknown, $hidden);
	}
}
