<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\Page;
use Tests\Support\AccessTokens;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * Event list of an events container (`categories` next to `events`): a guest
 * gets only categories of time periods a guest may see, a logged-in user the
 * categories of all time periods below the listed events. The test creates
 * its own project, events, categories and user account and removes them
 * again.
 */
final class EventListCategoriesTest extends ApiTestCase {
	private const USER_NAME = 'test-fixture-category-user';

	/** @var array<string, Page> */
	private static array $pages = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$pages = Fixtures::createCategoryTree();
	}

	public static function tearDownAfterClass(): void {
		AccessTokens::deleteAll();
		Fixtures::deleteCategoryTree();
		self::deleteUser();
		self::$pages = [];
		parent::tearDownAfterClass();
	}

	public function testGuestGetsOnlyCategoriesOfPeriodsReleasedForGuests(): void {
		$p = self::$pages;

		$response = $this->apiRequest('GET', 'tpage/' . $p['events']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertSame([$p['event']->id], array_column($response['json']['events'], 'id'), 'The event list itself must contain the published event only.');

		$categoryIds = array_column($response['json']['categories'], 'id');
		self::assertContains($p['category_guest']->id, $categoryIds);
		self::assertNotContains($p['category_members']->id, $categoryIds, 'Category of a period not released for guests.');
		self::assertNotContains($p['category_not_released']->id, $categoryIds, 'Category of a period with a release time in the future.');
		self::assertNotContains($p['category_hidden']->id, $categoryIds, 'Category of a hidden period.');
		self::assertNotContains($p['category_not_listed']->id, $categoryIds, 'Category of a period that is not in the field time_periods of the event.');
	}

	public function testLoggedInUserGetsTheCategoriesOfAllPeriodsOfTheEvent(): void {
		$p = self::$pages;
		$user = self::createUser();

		$response = $this->apiRequest('GET', 'tpage/' . $p['events']->id, [AccessTokens::authorizationHeader($user->id)]);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertSame([$p['event']->id], array_column($response['json']['events'], 'id'), 'The event list itself must contain the published event only.');

		$categoryIds = array_column($response['json']['categories'], 'id');
		foreach (['guest', 'members', 'not_released', 'hidden', 'not_listed'] as $key) {
			self::assertContains($p['category_' . $key]->id, $categoryIds, 'Category of period_' . $key . '.');
		}
	}

	private static function createUser(): \ProcessWire\User {
		self::deleteUser();
		$user = wire('users')->add(self::USER_NAME);
		self::assertTrue($user->id > 0 && !$user->isGuest() && !$user->isSuperuser(), 'The test needs a logged-in user without edit rights.');

		return $user;
	}

	private static function deleteUser(): void {
		foreach (wire('users')->find('name=' . self::USER_NAME . ', include=all') as $user) {
			wire('users')->delete($user);
		}
	}
}
