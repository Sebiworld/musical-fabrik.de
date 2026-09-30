<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\Page;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * Route `performances/{id}`, requested without a session. The test creates
 * its own project with performances, casts and roles and removes it again.
 */
final class PerformanceRoutesTest extends ApiTestCase {
	/** @var array<string, Page> */
	private static array $pages = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$pages = Fixtures::createPerformanceTree();
	}

	public static function tearDownAfterClass(): void {
		Fixtures::deletePerformanceTree();
		self::$pages = [];
		parent::tearDownAfterClass();
	}

	public function testPerformanceHasTheContractFields(): void {
		$p = self::$pages;

		$response = $this->apiRequest('GET', 'performances/' . $p['period_cast_a']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$json = $response['json'];
		self::assertSame(
			['id', 'title', 'timestamp', 'timestamp_until', 'admission_minutes', 'ticket_url', 'description', 'visitor_info', 'event', 'project', 'seasons', 'casts', 'categories', 'location', 'roles', 'hash'],
			array_keys($json)
		);
		self::assertSame($p['period_cast_a']->id, $json['id']);
		self::assertSame('Test Period Cast A', $json['title']);
		self::assertSame(1893484800, $json['timestamp']);
		self::assertSame(1893495600, $json['timestamp_until']);
		self::assertSame('https://tickets.example.org/test', $json['ticket_url']);
		self::assertStringContainsString('Period description', (string) $json['description']);
		self::assertSame(['id' => $p['event']->id, 'title' => 'Test Event'], $json['event']);
		self::assertSame($p['project']->id, $json['project']['id']);
		self::assertSame('Test Fixture Performance Project', $json['project']['title']);
		self::assertStringEndsWith('/' . Fixtures::PERFORMANCE_PROJECT_NAME . '/', $json['project']['url']);
		self::assertSame([$p['season_1']->id], array_column($json['seasons'], 'id'));
		self::assertSame(['id', 'title', 'url'], array_keys($json['seasons'][0]));
		self::assertSame([$p['cast_a']->id], array_column($json['casts'], 'id'));
		self::assertSame('Test Cast A', $json['casts'][0]['title']);
		self::assertSame(['auffuehrung'], array_map(
			fn ($category) => wire('pages')->get((int) $category['id'])->name,
			$json['categories']
		));
		self::assertSame(['roles', 'seasons', 'casts', 'portraits', 'child_ids'], array_keys($json['roles']));
		self::assertNotEmpty($json['hash']);
	}

	public function testPerformanceValuesTakePrecedenceOverTheEvent(): void {
		$response = $this->apiRequest('GET', 'performances/' . self::$pages['period_cast_a']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertSame(20, $response['json']['admission_minutes']);
		self::assertStringContainsString('Period visitor info', (string) $response['json']['visitor_info']);
	}

	public function testEventValuesApplyWhenThePerformanceHasNone(): void {
		$response = $this->apiRequest('GET', 'performances/' . self::$pages['period_no_casts']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$json = $response['json'];
		self::assertSame(45, $json['admission_minutes']);
		self::assertStringContainsString('Event visitor info', (string) $json['visitor_info']);
		self::assertNull($json['ticket_url']);
		self::assertNull($json['description']);
		self::assertNull($json['timestamp_until']);
		self::assertSame([], $json['casts']);
	}

	public function testLocationIsDelivered(): void {
		$p = self::$pages;

		$response = $this->apiRequest('GET', 'performances/' . $p['period_cast_a']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$location = $response['json']['location'];
		self::assertSame(['id', 'title', 'address', 'lat', 'lng', 'directions', 'accessibility_info'], array_keys($location));
		self::assertSame($p['location']->id, $location['id']);
		self::assertSame('Test Location', $location['title']);
		self::assertStringContainsString('Test Street 1', $location['address']);
		self::assertStringContainsString('Test directions', $location['directions']);
		self::assertNull($location['lat']);
		self::assertNull($location['lng']);
	}

	public function testLocationAccessibilityInfoIsDelivered(): void {
		$location = self::$pages['location'];
		$location->of(false);
		$location->set('accessibility_info', '<p>Test accessibility</p>');
		wire('pages')->save($location, ['quiet' => true]);

		$response = $this->apiRequest('GET', 'performances/' . self::$pages['period_cast_a']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertStringContainsString('Test accessibility', (string) $response['json']['location']['accessibility_info']);
	}

	public function testLocationIsNullWithoutLocation(): void {
		$response = $this->apiRequest('GET', 'performances/' . self::$pages['period_no_casts']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertArrayHasKey('location', $response['json']);
		self::assertNull($response['json']['location']);
	}

	public function testRolesKeepOnlyEntriesOfThePlayingCasts(): void {
		$p = self::$pages;

		$response = $this->apiRequest('GET', 'performances/' . $p['period_cast_a']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$roles = $response['json']['roles'];

		$mixed = $roles['roles'][$p['role_mixed']->id]['participants'];
		self::assertCount(2, $mixed);
		self::assertSame([$p['cast_a']->id], $mixed[0]['cast_ids']);
		self::assertSame([$p['portrait_a']->id], $mixed[0]['portrait_ids']);
		self::assertArrayNotHasKey('cast_ids', $mixed[1]);
		self::assertSame([$p['portrait_c']->id], $mixed[1]['portrait_ids']);

		// Roles stay in the tree without entries.
		self::assertSame([], $roles['roles'][$p['role_cast_b']->id]['participants']);
		self::assertSame([], $roles['roles'][$p['role_other_season']->id]['participants']);

		self::assertSame($this->sorted([$p['portrait_a']->id, $p['portrait_c']->id]), $this->sortedKeys($roles['portraits']));
		self::assertSame([$p['cast_a']->id], $this->sortedKeys($roles['casts']));
		self::assertSame([], $roles['seasons']);
	}

	public function testPerformanceWithoutCastsKeepsAllCastEntries(): void {
		$p = self::$pages;

		$response = $this->apiRequest('GET', 'performances/' . $p['period_no_casts']->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$roles = $response['json']['roles'];
		self::assertCount(3, $roles['roles'][$p['role_mixed']->id]['participants']);
		self::assertCount(1, $roles['roles'][$p['role_cast_b']->id]['participants']);
		// The season filter still applies: the event only belongs to season 1.
		self::assertSame([], $roles['roles'][$p['role_other_season']->id]['participants']);
		self::assertSame(
			$this->sorted([$p['portrait_a']->id, $p['portrait_b']->id, $p['portrait_c']->id]),
			$this->sortedKeys($roles['portraits'])
		);
		// Without playing casts, the casts referenced by the roles stay.
		self::assertSame($this->sorted([$p['cast_a']->id, $p['cast_b']->id]), $this->sortedKeys($roles['casts']));
	}

	public function testUnchangedHashAnswersNoContent(): void {
		$id = self::$pages['period_cast_a']->id;
		$first = $this->apiRequest('GET', 'performances/' . $id);
		self::assertSame(200, $first['status'], $first['raw']);

		$response = $this->apiRequest('GET', 'performances/' . $id . '?hash=' . $first['json']['hash']);

		self::assertSame(204, $response['status'], $response['raw']);
		self::assertSame('', $response['raw']);
	}

	public function testUnknownIdIsNotFound(): void {
		$this->assertPerformanceNotFound(2147483000);
	}

	public function testPageThatIsNoPerformanceIsNotFound(): void {
		$this->assertPerformanceNotFound(self::$pages['event']->id);
		$this->assertPerformanceNotFound(self::$pages['project']->id);
	}

	public function testPerformanceNotReleasedForGuestsIsNotFound(): void {
		$this->assertPerformanceNotFound(self::$pages['period_not_for_guests']->id);
	}

	public function testUnpublishedPerformanceIsNotFound(): void {
		$this->assertPerformanceNotFound(self::$pages['period_unpublished']->id);
	}

	public function testPerformanceWithReleaseTimeInTheFutureIsNotFound(): void {
		$this->assertPerformanceNotFound(self::$pages['period_not_released']->id);
	}

	public function testPerformanceBelowUnpublishedEventIsNotFound(): void {
		$event = self::$pages['event'];
		$event->of(false);
		$event->addStatus(Page::statusUnpublished);
		wire('pages')->save($event, ['quiet' => true]);

		try {
			$this->assertPerformanceNotFound(self::$pages['period_cast_a']->id);
		} finally {
			$event->removeStatus(Page::statusUnpublished);
			wire('pages')->save($event, ['quiet' => true]);
		}
	}

	public function testPerformanceInTheTrashIsNotFound(): void {
		$period = self::$pages['period_for_trash'];
		$first = $this->apiRequest('GET', 'performances/' . $period->id);
		self::assertSame(200, $first['status'], $first['raw']);

		wire('pages')->trash($period);

		try {
			$this->assertPerformanceNotFound($period->id);
		} finally {
			wire('pages')->restore($period);
		}
	}

	public function testPerformanceOfUnpublishedProjectIsNotFound(): void {
		$project = self::$pages['project'];
		$project->of(false);
		$project->addStatus(Page::statusUnpublished);
		wire('pages')->save($project, ['quiet' => true]);

		try {
			$this->assertPerformanceNotFound(self::$pages['period_cast_a']->id);
		} finally {
			$project->removeStatus(Page::statusUnpublished);
			wire('pages')->save($project, ['quiet' => true]);
		}
	}

	private function assertPerformanceNotFound(int $id): void {
		$response = $this->apiRequest('GET', 'performances/' . $id);

		self::assertSame(404, $response['status'], $response['raw']);
		self::assertSame('performance_not_found', $response['json']['errorcode']);
	}

	/**
	 * @return list<int>
	 */
	private function sortedKeys(array $map): array {
		return $this->sorted(array_map('intval', array_keys($map)));
	}

	/**
	 * @return list<int>
	 */
	private function sorted(array $ids): array {
		$ids = array_map('intval', $ids);
		sort($ids);

		return $ids;
	}
}
