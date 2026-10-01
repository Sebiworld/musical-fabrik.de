<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
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
		Fixtures::setPerformanceTimes(self::$pages['next_later'], self::BASE + 172800, null);
		Fixtures::resetNextAdmissionMinutes(self::$pages);
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
		Fixtures::setInteger($b, 'admission_minutes', 120);

		$result = $this->service->findCurrentAndNextPerformances(self::$pages['project'], $now);

		self::assertSame($b->id, $result['current']?->id);
		self::assertSame($a->id, $result['next']?->id);
	}

	public function testLaterPerformanceInHallAdmissionIsCurrentBeforeAnEarlierOne(): void {
		// Like above, but the long admission of B is its hall admission, and
		// A has a short admission of its own.
		$now = self::BASE - 6000;
		$a = self::$pages['next_later'];
		$b = self::$pages['next_main'];
		Fixtures::setPerformanceTimes($a, $now + 3600, null);
		Fixtures::setPerformanceTimes($b, $now + 5400, null);
		Fixtures::setInteger(self::$pages['event'], 'admission_minutes', null);
		Fixtures::setInteger(self::$pages['project'], 'hall_admission_minutes', null);
		Fixtures::setInteger($a, 'admission_minutes', 10);
		Fixtures::setInteger($b, 'hall_admission_minutes', 120);

		$result = $this->service->findCurrentAndNextPerformances(self::$pages['project'], $now);

		self::assertSame($b->id, $result['current']?->id);
		self::assertSame($a->id, $result['next']?->id);
	}

	public function testLaterPerformanceInProjectAdmissionIsCurrentBeforeAnEarlierOne(): void {
		// Like above, but the long admission of B comes from the project.
		$now = self::BASE - 6000;
		$a = self::$pages['next_later'];
		$b = self::$pages['next_main'];
		Fixtures::setPerformanceTimes($a, $now + 3600, null);
		Fixtures::setPerformanceTimes($b, $now + 5400, null);
		Fixtures::setInteger(self::$pages['event'], 'admission_minutes', null);
		Fixtures::setInteger(self::$pages['project'], 'hall_admission_minutes', null);
		Fixtures::setInteger(self::$pages['project'], 'admission_minutes', 120);
		Fixtures::setInteger($a, 'admission_minutes', 10);
		Fixtures::setInteger($a, 'hall_admission_minutes', 10);

		$result = $this->service->findCurrentAndNextPerformances(self::$pages['project'], $now);

		self::assertSame($b->id, $result['current']?->id);
		self::assertSame($a->id, $result['next']?->id);
	}

	/**
	 * @return array<string, array{array<string, array<string, int|null>>, int}>
	 */
	public static function admissionStartProvider(): array {
		return [
			'foyer from the project' => [['event' => ['admission_minutes' => null], 'project' => ['admission_minutes' => 60, 'hall_admission_minutes' => null]], 60],
			'hall only' => [['event' => ['admission_minutes' => null], 'project' => ['hall_admission_minutes' => null], 'next_main' => ['hall_admission_minutes' => 30]], 30],
			'hall longer than foyer' => [['project' => ['hall_admission_minutes' => 90]], 90],
			'no admission' => [['event' => ['admission_minutes' => null], 'project' => ['hall_admission_minutes' => null]], 0],
			'foyer zero, hall 30' => [['next_main' => ['admission_minutes' => 0, 'hall_admission_minutes' => 30]], 30],
			'both zero' => [['next_main' => ['admission_minutes' => 0, 'hall_admission_minutes' => 0], 'project' => ['admission_minutes' => 60, 'hall_admission_minutes' => 40]], 0],
		];
	}

	/**
	 * The performance is current from its beginning minus the longer of the
	 * foyer and the hall admission, each with its own fallback.
	 *
	 * @param array<string, array<string, int|null>> $values
	 */
	#[DataProvider('admissionStartProvider')]
	public function testCurrentFromTheLongerAdmission(array $values, int $minutes): void {
		$this->setValues($values);
		$begin = self::BASE + 7200;
		$project = self::$pages['project'];

		$before = $this->service->findCurrentAndNextPerformances($project, $begin - $minutes * 60 - 1);
		$at = $this->service->findCurrentAndNextPerformances($project, $begin - $minutes * 60);

		// Ids only: a failing comparison of pages would dump the whole page.
		self::assertNull($before['current']?->id);
		self::assertSame(self::$pages['next_main']->id, $before['next']?->id);
		self::assertSame(self::$pages['next_main']->id, $at['current']?->id);
		self::assertSame(self::$pages['next_later']->id, $at['next']?->id);
	}

	/**
	 * @return array<string, array{array<string, array<string, int|null>>, int|null, int|null}>
	 */
	public static function admissionPrecedenceProvider(): array {
		$all = [
			'project' => ['admission_minutes' => 60, 'hall_admission_minutes' => 40],
			'event' => ['admission_minutes' => 45, 'hall_admission_minutes' => 30],
			'next_main' => ['admission_minutes' => 5, 'hall_admission_minutes' => 3],
		];

		return [
			'performance' => [$all, 5, 3],
			'event' => [array_merge($all, ['next_main' => ['admission_minutes' => null, 'hall_admission_minutes' => null]]), 45, 30],
			'project' => [array_merge($all, [
				'event' => ['admission_minutes' => null, 'hall_admission_minutes' => null],
				'next_main' => ['admission_minutes' => null, 'hall_admission_minutes' => null],
			]), 60, 40],
			'foyer from the project, hall from the performance' => [[
				'project' => ['admission_minutes' => 60, 'hall_admission_minutes' => 40],
				'event' => ['admission_minutes' => null, 'hall_admission_minutes' => 30],
				'next_main' => ['admission_minutes' => null, 'hall_admission_minutes' => 3],
			], 60, 3],
			'zero on the performance' => [array_merge($all, ['next_main' => ['admission_minutes' => 0, 'hall_admission_minutes' => 0]]), 0, 0],
			'zero on the event' => [array_merge($all, [
				'event' => ['admission_minutes' => 0, 'hall_admission_minutes' => 0],
				'next_main' => ['admission_minutes' => null, 'hall_admission_minutes' => null],
			]), 0, 0],
			'none' => [[
				'project' => ['admission_minutes' => null, 'hall_admission_minutes' => null],
				'event' => ['admission_minutes' => null, 'hall_admission_minutes' => null],
			], null, null],
		];
	}

	/**
	 * Each admission field falls back on its own from the performance to the
	 * event to the project. An empty field falls back, 0 is a value.
	 *
	 * @param array<string, array<string, int|null>> $values
	 */
	#[DataProvider('admissionPrecedenceProvider')]
	public function testAdmissionMinutesFallBackPerField(array $values, ?int $foyer, ?int $hall): void {
		$this->setValues($values);

		$summary = $this->service->getPerformanceSummaryAjax(self::$pages['next_main']);

		self::assertSame($foyer, $summary['admission_minutes']);
		self::assertArrayHasKey('hall_admission_minutes', $summary);
		self::assertSame($hall, $summary['hall_admission_minutes']);
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

	/**
	 * @param array<string, array<string, int|null>> $values field values by fixture key
	 */
	private function setValues(array $values): void {
		foreach ($values as $key => $fields) {
			foreach ($fields as $field => $value) {
				Fixtures::setInteger(self::$pages[$key], $field, $value);
			}
		}
	}
}
