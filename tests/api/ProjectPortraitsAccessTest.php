<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\Page;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

/**
 * Route `project-portraits?ids=…`: portraits a guest may not view are left
 * out. Creates its own portraits and removes them afterwards.
 */
final class ProjectPortraitsAccessTest extends ApiTestCase {
	/** @var array<string, Page>|null */
	private static ?array $pages = null;

	protected function setUp(): void {
		parent::setUp();

		if (self::$pages === null) {
			self::$pages = Fixtures::createPortraitTree();
		}
	}

	public static function tearDownAfterClass(): void {
		if (self::$pages !== null) {
			Fixtures::deletePortraitTree();
			self::$pages = null;
		}
		parent::tearDownAfterClass();
	}

	public function testGuestsOnlyGetPortraitsTheyMayView(): void {
		$ids = array_map(fn (Page $page) => $page->id, self::$pages);

		$response = $this->apiRequest('GET', 'project-portraits?ids=' . implode(',', $ids));

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertSame([(string) self::$pages['visible']->id], array_map('strval', array_keys($response['json']['portraits'])));
		self::assertSame('Test Portrait Visible', $response['json']['portraits'][self::$pages['visible']->id]['title']);
	}

	public function testAHiddenPortraitAloneGivesAnEmptyResult(): void {
		foreach (['unpublished', 'not_released', 'expired'] as $key) {
			$response = $this->apiRequest('GET', 'project-portraits?ids=' . self::$pages[$key]->id);

			self::assertSame(200, $response['status'], $key . ': ' . $response['raw']);
			self::assertSame([], $response['json']['portraits'], $key);
		}
	}
}
