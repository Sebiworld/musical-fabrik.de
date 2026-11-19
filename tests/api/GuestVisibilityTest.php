<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\Page;
use Tests\Support\AccessTokens;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * Lists and cards show a guest only what a guest may see: the event list of
 * an events container (selector input, counts, periods), event cards of the
 * page list, the performances of a project, roles with their portraits,
 * casts and seasons, and the cards of a pages grid. The test creates its own
 * pages and a user account and removes them again.
 */
final class GuestVisibilityTest extends ApiTestCase {
	private const USER_NAME = 'test-fixture-visibility-user';

	/** Upper limit of `limit` in the event list. */
	private const EVENT_LIST_MAX_LIMIT = 100;

	/** @var array<string, Page> */
	private static array $pages = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$pages = Fixtures::createGuestVisibilityTree();
	}

	public static function tearDownAfterClass(): void {
		AccessTokens::deleteAll();
		Fixtures::deleteGuestVisibilityTree();
		self::deleteUser();
		self::$pages = [];
		parent::tearDownAfterClass();
	}

	public function testEventListCountsOnlyEventsAGuestMaySee(): void {
		$p = self::$pages;

		$list = $this->eventList();
		self::assertSame([$p['event']->id, $p['event_early']->id], array_column($list['events'], 'id'));
		self::assertSame(2, $list['totalNumber']);
		self::assertFalse($list['moreAvailable']);

		foreach (['Qzunpublished', 'Qzunreleased', 'Qzlocked'] as $query) {
			$list = $this->eventList(['q' => $query]);
			self::assertSame([], $list['events'], $query);
			self::assertSame(0, $list['totalNumber'], 'An event a guest may not see is counted: q=' . $query);
		}

		self::assertSame(1, $this->eventList(['q' => 'Qzvisible'])['totalNumber'], 'Control: the search finds a visible event.');
	}

	public function testSelectorPartsInSortAreIgnored(): void {
		$plain = $this->eventList();

		foreach (['-datetime_from, include=all', '-datetime_from, check_access=0', 'title, include=all, title^=Test Event Qzunpublished'] as $sort) {
			$list = $this->eventList(['sort' => $sort]);
			self::assertSame($plain['totalNumber'], $list['totalNumber'], 'sort=' . $sort);
			self::assertSame(array_column($plain['events'], 'id'), array_column($list['events'], 'id'), 'sort=' . $sort);
		}
	}

	public function testSelectorPartsInTheQueryAreIgnored(): void {
		self::assertSame(0, $this->eventList(['q' => 'Qzunpublished'])['totalNumber'], 'Control without selector parts.');
		self::assertSame(0, $this->eventList(['q' => 'Qzunpublished, include=all'])['totalNumber']);
		self::assertSame(0, $this->eventList(['q' => 'Qzunreleased, check_access=0'])['totalNumber']);
	}

	public function testUnknownSortValuesFallBackToTheDefaultOrder(): void {
		$p = self::$pages;
		$newestFirst = [$p['event']->id, $p['event_early']->id];

		self::assertSame($newestFirst, array_column($this->eventList()['events'], 'id'), 'Default order: newest first.');
		self::assertSame(array_reverse($newestFirst), array_column($this->eventList(['sort' => 'datetime_from'])['events'], 'id'), 'Allowed value: oldest first.');
		// Sorted by title, the early event ("Aearly") would come first.
		self::assertSame($newestFirst, array_column($this->eventList(['sort' => 'title'])['events'], 'id'), 'Unknown value: default order.');
	}

	public function testEventListLimitHasAnUpperBound(): void {
		$list = $this->eventList(['limit' => 100000]);

		self::assertSame(self::EVENT_LIST_MAX_LIMIT, $list['lastElementIndex']);
		self::assertSame(2, $list['totalNumber']);
	}

	public function testEventListShowsOnlyPeriodsAGuestMaySee(): void {
		$p = self::$pages;

		$events = array_column($this->eventList()['events'], null, 'id');

		self::assertSame([$p['period_guest']->id], array_column($events[$p['event']->id]['time_periods'], 'id'));
	}

	public function testEventCardsOfThePageListShowOnlyPeriodsAGuestMaySee(): void {
		$p = self::$pages;

		$item = $this->pageListEvent();

		self::assertSame([$p['period_guest']->id], array_column($item['time_periods'], 'id'));
	}

	public function testEventCardsKeepTheMembersPeriodsForALoggedInUser(): void {
		$p = self::$pages;
		$user = self::createUser();

		$item = $this->pageListEvent([AccessTokens::authorizationHeader($user->id)]);

		self::assertSame([$p['period_guest']->id, $p['period_members']->id], array_column($item['time_periods'], 'id'), 'A logged-in user sees periods for members, but no period he may not view.');
	}

	public function testProjectPerformancesAreTheGuestPeriodsForEverybody(): void {
		$p = self::$pages;
		$expected = self::sortedIds([['id' => $p['period_early']->id], ['id' => $p['period_guest']->id]]);

		$guest = $this->apiRequest('GET', 'projects/' . $p['project']->id);
		self::assertSame(200, $guest['status'], $guest['raw']);
		self::assertSame($expected, self::sortedIds($guest['json']['events']['performances']));
		self::assertSame(2, $guest['json']['events']['performances_count']);

		$superuser = $this->apiRequest('GET', 'projects/' . $p['project']->id, [AccessTokens::authorizationHeader(AccessTokens::superuserId())]);
		self::assertSame(200, $superuser['status'], $superuser['raw']);
		self::assertSame($expected, self::sortedIds($superuser['json']['events']['performances']), 'The performances box shows the guest periods also to a superuser.');
	}

	public function testHiddenPerformanceStaysReachableByItsId(): void {
		$p = self::$pages;

		self::assertSame(200, $this->apiRequest('GET', 'performances/' . $p['period_hidden']->id)['status'], 'Hidden means "not listed", not "locked".');
		self::assertSame(404, $this->apiRequest('GET', 'performances/' . $p['period_not_released']->id)['status'], 'Control.');
	}

	public function testRolesShowOnlyPortraitsCastsAndSeasonsAGuestMaySee(): void {
		$p = self::$pages;
		$visible = $p['portrait_visible']->id;
		$expected = [
			'portraits' => [],
			'casts' => [$p['cast_visible']->id],
			'seasons' => [$p['season_visible']->id],
			'portrait_ids' => [[$visible], [$visible], [$visible]],
			'cast_ids' => $p['cast_visible']->id,
			'season_ids' => $p['season_visible']->id,
		];

		foreach (['role', 'roles_container', 'project'] as $key) {
			$response = $this->apiRequest('GET', 'project-roles/' . $p[$key]->id);
			self::assertSame(200, $response['status'], $response['raw']);
			$json = $response['json'];
			$participants = $json['roles'][$p['role']->id]['participants'];
			// portrait_member is visible, but only named in entries of hidden casts and seasons.
			$expected['portraits'] = [$visible];

			self::assertSame($expected, [
				'portraits' => self::sortedIds(array_map(fn ($id) => ['id' => $id], array_keys($json['portraits']))),
				'casts' => array_map('intval', array_keys($json['casts'])),
				'seasons' => array_map('intval', array_keys($json['seasons'])),
				'portrait_ids' => array_column($participants, 'portrait_ids'),
				// Each of these lists is expected to hold the visible page only.
				'cast_ids' => count($participants[1]['cast_ids']) === 1 ? $participants[1]['cast_ids'][0] : $participants[1]['cast_ids'],
				'season_ids' => count($participants[2]['season_ids']) === 1 ? $participants[2]['season_ids'][0] : $participants[2]['season_ids'],
			], 'project-roles/{' . $key . '}');
		}
	}

	public function testPerformanceShowsOnlyPortraitsAGuestMaySee(): void {
		$p = self::$pages;

		$response = $this->apiRequest('GET', 'performances/' . $p['period_guest']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertSame([$p['portrait_visible']->id], array_map('intval', array_keys($response['json']['roles']['portraits'])));
	}

	public function testCastAndSeasonLinksShowOnlyPagesAGuestMaySee(): void {
		$p = self::$pages;
		$expected = ['casts' => [$p['cast_visible']->id], 'seasons' => [$p['season_visible']->id]];

		$performance = $this->apiRequest('GET', 'performances/' . $p['period_guest']->id);
		self::assertSame(200, $performance['status'], $performance['raw']);

		$project = $this->apiRequest('GET', 'projects/' . $p['project']->id);
		self::assertSame(200, $project['status'], $project['raw']);
		$performances = array_column($project['json']['events']['performances'], null, 'id');
		self::assertArrayHasKey($p['period_guest']->id, $performances);

		self::assertSame([
			'performances/{id}' => $expected,
			'projects/{id} events.performances' => $expected,
		], [
			'performances/{id}' => [
				'casts' => array_column($performance['json']['casts'], 'id'),
				'seasons' => array_column($performance['json']['seasons'], 'id'),
			],
			'projects/{id} events.performances' => [
				'casts' => array_column($performances[$p['period_guest']->id]['casts'], 'id'),
				'seasons' => array_column($performances[$p['period_guest']->id]['seasons'], 'id'),
			],
		]);
	}

	public function testLegacyCastOfEventCardsIsLeftOutIfAGuestMayNotSeeIt(): void {
		$p = self::$pages;

		$cards = [
			'page-list' => $this->pageListEvent(),
			'event list' => array_column($this->eventList()['events'], null, 'id')[$p['event']->id],
		];

		$actual = [];
		foreach ($cards as $label => $card) {
			$period = array_column($card['time_periods'], null, 'id')[$p['period_guest']->id];
			$actual[$label] = $period['cast'] ?? null;
		}

		self::assertSame(['page-list' => null, 'event list' => null], $actual, 'Legacy field cast of period_guest holds cast_not_released.');
	}

	public function testParticipantsOnlyInHiddenCastsOrSeasonsMatchNoPerformance(): void {
		$p = self::$pages;

		// period_early plays no cast and its event has no season: every entry
		// without cast or season matches, but none of a hidden cast or season.
		$response = $this->apiRequest('GET', 'performances/' . $p['period_early']->id);
		self::assertSame(200, $response['status'], $response['raw']);
		$roles = $response['json']['roles'];

		self::assertSame([
			'portraits' => [$p['portrait_visible']->id],
			'role_hidden_only participants' => [],
			'role participants' => 3,
		], [
			'portraits' => array_map('intval', array_keys($roles['portraits'])),
			'role_hidden_only participants' => $roles['roles'][$p['role_hidden_only']->id]['participants'] ?? 'missing',
			'role participants' => count($roles['roles'][$p['role']->id]['participants'] ?? []),
		]);
	}

	public function testRolesLeaveOutEntriesOfHiddenCastsOrSeasonsOnly(): void {
		$p = self::$pages;

		$response = $this->apiRequest('GET', 'project-roles/' . $p['role_hidden_only']->id);
		self::assertSame(200, $response['status'], $response['raw']);
		$json = $response['json'];

		// An empty cast_ids or season_ids means "all casts" or "all seasons"
		// to the client, so such an entry must not be delivered at all.
		self::assertSame([
			'participants' => [],
			'portraits' => [],
		], [
			'participants' => $json['roles'][$p['role_hidden_only']->id]['participants'] ?? 'missing',
			'portraits' => array_map('intval', array_keys($json['portraits'])),
		]);

		// Control: entries without any cast or season stay (role "role", entry 0).
		$role = $this->apiRequest('GET', 'project-roles/' . $p['role']->id)['json']['roles'][$p['role']->id];
		self::assertSame([$p['portrait_visible']->id], $role['participants'][0]['portrait_ids']);
	}

	public function testParticipantCountOfAProjectCountsOnlyPortraitsAGuestMaySee(): void {
		$p = self::$pages;

		$response = $this->apiRequest('GET', 'projects/' . $p['project']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$general = array_column($response['json']['general'], null, 'id');
		// portrait_member is only named in entries of hidden casts and seasons.
		self::assertSame(1, $general['participants_total']['rawValue'] ?? null);
	}

	public function testPagesGridLeavesOutPasswordLockedPages(): void {
		$p = self::$pages;

		$response = $this->apiRequest('GET', 'tpage/' . $p['grid_host']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$cards = self::findKey($response['json'], 'cards');
		self::assertIsArray($cards, 'The pages grid has cards.');
		self::assertSame([$p['open']->id], array_column($cards, 'id'));
		self::assertStringNotContainsString('Test Locked Grid Intro', $response['raw']);
	}

	/**
	 * @return array<string, mixed> the event list of the events container
	 */
	private function eventList(array $query = []): array {
		$path = 'tpage/' . self::$pages['events']->id . ($query ? '?' . http_build_query($query) : '');
		$response = $this->apiRequest('GET', $path);
		self::assertSame(200, $response['status'], $path . ': ' . $response['raw']);
		$list = self::findEventList($response['json']);
		self::assertIsArray($list, $path . ': no event list in ' . $response['raw']);

		return $list;
	}

	/**
	 * @return array<string, mixed>|null the first object with `events` and `totalNumber`
	 */
	private static function findEventList(mixed $data): ?array {
		if (!is_array($data)) {
			return null;
		}
		if (isset($data['events'], $data['totalNumber']) && is_array($data['events'])) {
			return $data;
		}
		foreach ($data as $value) {
			$found = self::findEventList($value);
			if ($found !== null) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * @return array<string, mixed> the card of "event" in the page list of the project
	 */
	private function pageListEvent(array $headers = []): array {
		$p = self::$pages;
		$response = $this->apiRequest('GET', 'page-list/items/' . $p['project']->id . '?templates=event&limit=50', $headers);
		self::assertSame(200, $response['status'], $response['raw']);
		$items = array_column($response['json']['items'], null, 'id');
		self::assertArrayHasKey($p['event']->id, $items);

		return $items[$p['event']->id];
	}

	/**
	 * @return int[]
	 */
	private static function sortedIds(array $items): array {
		$ids = array_map('intval', array_column($items, 'id'));
		sort($ids);

		return $ids;
	}

	private static function findKey(mixed $data, string $key): mixed {
		if (!is_array($data)) {
			return null;
		}
		if (array_key_exists($key, $data)) {
			return $data[$key];
		}
		foreach ($data as $value) {
			$found = self::findKey($value, $key);
			if ($found !== null) {
				return $found;
			}
		}

		return null;
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
