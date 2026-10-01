<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\Page;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * PerformancesService::findCurrentAndNextPerformances() with an injected
 * clock, as guest. The performances lie far in the future, so that no
 * performance of the test database interferes with the search over all
 * projects.
 */
final class NextPerformancesTest extends TestCase {
	private const BASE = 3786912000;

	/** @var array<string, Page> */
	private static array $pages = [];

	private object $service;

	public static function setUpBeforeClass(): void {
		self::$pages = Fixtures::createNextPerformanceTree(self::BASE);
	}

	public static function tearDownAfterClass(): void {
		Fixtures::deleteNextPerformanceTree();
		self::$pages = [];
	}

	protected function setUp(): void {
		$this->service = wire('modules')->get('Twack')->getService('PerformancesService');
		Fixtures::setPerformanceTimes(self::$pages['next_main'], self::BASE + 7200, self::BASE + 14400);
	}

	public function testAllProjectsBeforeAnyAdmission(): void {
		// The hidden performances of the first project would already be in
		// admission (from BASE - 15 min), the other project has none.
		$result = $this->service->findCurrentAndNextPerformances(null, self::BASE - 600);

		self::assertNull($result['current']);
		self::assertSame(self::$pages['other_soon']->id, $result['next']->id);
	}

	public function testAdmissionStartsAdmissionMinutesBeforeTheBeginning(): void {
		$begin = self::BASE + 7200;
		$project = self::$pages['project'];

		$before = $this->service->findCurrentAndNextPerformances($project, $begin - 45 * 60 - 1);
		$at = $this->service->findCurrentAndNextPerformances($project, $begin - 45 * 60);

		self::assertNull($before['current']);
		self::assertSame(self::$pages['next_main']->id, $before['next']->id);
		self::assertSame(self::$pages['next_main']->id, $at['current']->id);
		self::assertSame(self::$pages['next_later']->id, $at['next']->id);
	}

	public function testLaterPerformanceInAdmissionIsCurrentBeforeAnEarlierOne(): void {
		// A begins earlier, its admission (45 min from the event) has not
		// begun. B begins later, but its admission of 120 min has begun.
		$now = self::BASE - 6000;
		$a = self::$pages['next_later'];
		$b = self::$pages['next_main'];
		Fixtures::setPerformanceTimes($a, $now + 3600, null);
		Fixtures::setPerformanceTimes($b, $now + 5400, null);
		$this->setAdmissionMinutes($b, 120);

		try {
			$result = $this->service->findCurrentAndNextPerformances(self::$pages['project'], $now);
		} finally {
			Fixtures::setPerformanceTimes($a, self::BASE + 172800, null);
			$this->setAdmissionMinutes($b, null);
		}

		self::assertSame($b->id, $result['current']?->id);
		self::assertSame($a->id, $result['next']?->id);
	}

	public function testEndIsExclusive(): void {
		$project = self::$pages['project'];

		$last = $this->service->findCurrentAndNextPerformances($project, self::BASE + 14399);
		$end = $this->service->findCurrentAndNextPerformances($project, self::BASE + 14400);

		self::assertSame(self::$pages['next_main']->id, $last['current']->id);
		self::assertNull($end['current']);
		self::assertSame(self::$pages['next_later']->id, $end['next']->id);
	}

	public function testSeveralCurrentPerformancesTakeTheEarliestBeginning(): void {
		// Other project: running since 10 min (no end). next_main: admission
		// has begun. Over all projects, the earlier beginning wins.
		$now = self::BASE + 3600 + 600;
		Fixtures::setPerformanceTimes(self::$pages['next_main'], $now + 1200, null);

		$result = $this->service->findCurrentAndNextPerformances(null, $now);

		self::assertSame(self::$pages['other_soon']->id, $result['current']->id);
		self::assertSame(self::$pages['next_main']->id, $result['next']->id);
	}

	public function testOtherCategoryFixtureIsNoPerformance(): void {
		$names = self::$pages['next_other_category']->event_categories->explode('name');

		self::assertSame(['generalprobe'], $names);
	}

	public function testNoPerformanceAfterTheLast(): void {
		$result = $this->service->findCurrentAndNextPerformances(self::$pages['project'], self::BASE + 172800 + 3 * 3600);

		self::assertNull($result['current']);
		self::assertNull($result['next']);
	}

	private function setAdmissionMinutes(Page $period, ?int $minutes): void {
		$period->of(false);
		$period->set('admission_minutes', $minutes ?? '');
		wire('pages')->save($period, ['quiet' => true]);
	}
}
