<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\Page;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * Route `performances/next`, requested without a session. The test creates
 * its own projects with performances around the current time and removes
 * them again. The route uses the real clock, so the performances are placed
 * with margins of several minutes.
 */
final class NextPerformanceRoutesTest extends ApiTestCase {
	private const ITEM_KEYS = ['id', 'title', 'timestamp', 'timestamp_until', 'admission_minutes', 'ticket_url', 'event', 'project', 'casts'];

	/** @var array<string, Page> */
	private static array $pages = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$pages = Fixtures::createNextPerformanceTree(time());
	}

	public static function tearDownAfterClass(): void {
		Fixtures::deleteNextPerformanceTree();
		self::$pages = [];
		parent::tearDownAfterClass();
	}

	public function testBeforeAdmissionOnlyNextIsSet(): void {
		$now = time();
		// Starts in 2 h, admission (45 min from the event) begins in 75 min.
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now + 7200, $now + 14400);

		$json = $this->requestNext();

		self::assertNull($json['current']);
		self::assertSame(self::$pages['next_main']->id, $json['next']['id']);
	}

	public function testDuringAdmissionThePerformanceIsCurrent(): void {
		$now = time();
		// Starts in 20 min, admission began 25 min ago.
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now + 1200, $now + 8400);

		$json = $this->requestNext();

		self::assertSame(self::$pages['next_main']->id, $json['current']['id']);
		self::assertSame(self::$pages['next_later']->id, $json['next']['id']);
	}

	public function testDuringThePerformanceItIsCurrentAndTheFollowingIsNext(): void {
		$now = time();
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now - 1800, $now + 3600);

		$json = $this->requestNext();

		self::assertSame(self::$pages['next_main']->id, $json['current']['id']);
		self::assertSame(self::$pages['next_later']->id, $json['next']['id']);
	}

	public function testWithoutEndThePerformanceIsCurrentForThreeHours(): void {
		$now = time();
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now - 9000, null);
		$running = $this->requestNext();

		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now - 12000, null);
		$ended = $this->requestNext();

		self::assertSame(self::$pages['next_main']->id, $running['current']['id']);
		self::assertNull($running['current']['timestamp_until']);
		self::assertNull($ended['current']);
		self::assertSame(self::$pages['next_later']->id, $ended['next']['id']);
	}

	public function testAfterTheEndThePerformanceIsGone(): void {
		$now = time();
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now - 10800, $now - 600);

		$json = $this->requestNext();

		self::assertNull($json['current']);
		self::assertSame(self::$pages['next_later']->id, $json['next']['id']);
	}

	public function testItemHasTheContractFields(): void {
		$now = time();
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now + 7200, $now + 14400);
		$p = self::$pages;

		$json = $this->requestNext();

		self::assertSame(['current', 'next', 'hash'], array_keys($json));
		$item = $json['next'];
		self::assertSame(self::ITEM_KEYS, array_keys($item));
		self::assertSame('Test next_main', $item['title']);
		self::assertSame($now + 7200, $item['timestamp']);
		self::assertSame($now + 14400, $item['timestamp_until']);
		self::assertSame(45, $item['admission_minutes']);
		self::assertNull($item['ticket_url']);
		self::assertSame(['id' => $p['event']->id, 'title' => 'Test Next Event'], $item['event']);
		self::assertSame(['id', 'title', 'url'], array_keys($item['project']));
		self::assertSame($p['project']->id, $item['project']['id']);
		self::assertStringEndsWith('/' . Fixtures::NEXT_PROJECT_NAME . '/', $item['project']['url']);
		self::assertSame([], $item['casts']);
		self::assertNotEmpty($json['hash']);
	}

	public function testItemEqualsTheDetailRoute(): void {
		$now = time();
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now + 7200, $now + 14400);

		$next = $this->requestNext()['next'];
		$detail = $this->apiRequest('GET', 'performances/' . self::$pages['next_main']->id);

		self::assertSame(200, $detail['status'], $detail['raw']);
		foreach (self::ITEM_KEYS as $key) {
			self::assertSame($detail['json'][$key], $next[$key], $key);
		}
	}

	public function testHiddenPerformancesAreSkipped(): void {
		// The hidden performances start in 30 min, inside the admission time,
		// so they would be current if they were not skipped.
		$now = time();
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now + 7200, $now + 14400);
		$hiddenIds = array_map(fn ($key) => self::$pages[$key]->id, ['next_not_for_guests', 'next_unpublished', 'next_not_released', 'next_other_category']);

		$json = $this->requestNext();
		$global = $this->requestNext(null);

		self::assertNull($json['current']);
		self::assertSame(self::$pages['next_main']->id, $json['next']['id']);
		foreach ([$global['current'], $global['next']] as $item) {
			self::assertNotContains($item['id'] ?? null, $hiddenIds);
		}
	}

	public function testProjectFilterExcludesOtherProjects(): void {
		// other_soon (other project) starts in 1 h, before next_main.
		$now = time();
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now + 7200, $now + 14400);

		$json = $this->requestNext();
		$other = $this->requestNext(self::$pages['other_project']->id);

		self::assertSame(self::$pages['next_main']->id, $json['next']['id']);
		self::assertSame(self::$pages['other_soon']->id, $other['next']['id']);
	}

	public function testGlobalResultHasTheResponseShape(): void {
		$json = $this->requestNext(null);

		self::assertSame(['current', 'next', 'hash'], array_keys($json));
		foreach (['current', 'next'] as $key) {
			if ($json[$key] !== null) {
				self::assertSame(self::ITEM_KEYS, array_keys($json[$key]));
			}
		}
	}

	public function testInvalidProjectAnswersEmpty(): void {
		foreach ([2147483000, self::$pages['event']->id, 'abc'] as $project) {
			$response = $this->apiRequest('GET', 'performances/next?project=' . $project);

			self::assertSame(200, $response['status'], $response['raw']);
			self::assertNull($response['json']['current'], (string) $project);
			self::assertNull($response['json']['next'], (string) $project);
		}
	}

	public function testUnpublishedProjectAnswersEmpty(): void {
		$project = self::$pages['project'];
		$project->of(false);
		$project->addStatus(Page::statusUnpublished);
		wire('pages')->save($project, ['quiet' => true]);

		try {
			$json = $this->requestNext();
			self::assertNull($json['current']);
			self::assertNull($json['next']);
		} finally {
			$project->removeStatus(Page::statusUnpublished);
			wire('pages')->save($project, ['quiet' => true]);
		}
	}

	public function testUnchangedHashAnswersNoContent(): void {
		$now = time();
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now + 7200, $now + 14400);
		$path = 'performances/next?project=' . self::$pages['project']->id;
		$first = $this->apiRequest('GET', $path);
		self::assertSame(200, $first['status'], $first['raw']);

		$response = $this->apiRequest('GET', $path . '&hash=' . $first['json']['hash']);

		self::assertSame(204, $response['status'], $response['raw']);
		self::assertSame('', $response['raw']);
	}

	/**
	 * Requests the route for the "next" test project, or for all projects
	 * with $project = null.
	 */
	private function requestNext(int|false|null $project = false): array {
		if ($project === false) {
			$project = self::$pages['project']->id;
		}
		$response = $this->apiRequest('GET', 'performances/next' . ($project === null ? '' : '?project=' . $project));

		self::assertSame(200, $response['status'], $response['raw']);

		return $response['json'];
	}
}
