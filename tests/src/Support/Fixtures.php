<?php

declare(strict_types=1);
namespace Tests\Support;

use ProcessWire\Page;

use function ProcessWire\wire;

/**
 * Looks up the seed pages of the test database by path. IDs change every time
 * the test database is prepared, so tests must never hard-code them.
 */
final class Fixtures {
	public const CONTENT_PATH = '/test-fixture-content/';
	public const UNPUBLISHED_PATH = '/test-fixture-unpublished/';
	public const PROJECT_PATH = '/projekte/test-fixture-project/';
	public const ROLE_1_PATH = '/projekte/test-fixture-project/rollen/test-fixture-role-1/';
	public const ROLE_2_PATH = '/projekte/test-fixture-project/rollen/test-fixture-role-2/';
	public const ROLE_3_PATH = '/projekte/test-fixture-project/rollen/test-fixture-role-3/';
	public const PORTRAIT_1_PATH = '/projekte/test-fixture-project/mitwirkenden_portraits/test-fixture-portrait-1/';
	public const PORTRAIT_2_PATH = '/projekte/test-fixture-project/mitwirkenden_portraits/test-fixture-portrait-2/';

	/**
	 * Finds a seed page, including unpublished ones. Fails loudly when the
	 * seed is missing, so that a wrongly prepared database is not mistaken
	 * for a passing test.
	 */
	public static function page(string $path): Page {
		$page = wire('pages')->get('path=' . $path . ', include=all');
		if (!$page->id) {
			throw new \RuntimeException('Seed page not found: ' . $path . '. Is the test database prepared?');
		}

		return $page;
	}

	public const PERFORMANCE_PROJECT_NAME = 'test-fixture-performance-project';
	public const PERFORMANCE_LOCATION_NAME = 'test-fixture-performance-location';

	/**
	 * Creates a project with casts, seasons, portraits, roles and one event
	 * with several performances (time_period pages). Returns the created
	 * pages by key. Remove them again with deletePerformanceTree().
	 *
	 * Performances:
	 * - period_cast_a: released for guests, plays cast A, has a location and its own values
	 * - period_no_casts: released for guests, no casts, no location, no own values
	 * - period_not_for_guests: not released for guests
	 * - period_unpublished: released for guests, but unpublished
	 * - period_not_released: released for guests, but with a release time in the future
	 * - period_for_trash: released for guests, for tests that move it to the trash
	 *
	 * Roles:
	 * - role_mixed: entries for cast A, cast B and one without cast
	 * - role_cast_b: one entry for cast B only
	 * - role_other_season: one entry for a season the event is not part of
	 *
	 * @return array<string, Page>
	 */
	public static function createPerformanceTree(): array {
		self::deletePerformanceTree();

		$pages = wire('pages');
		$p = [];

		$p['project'] = self::newPage('project', $pages->get('template=projects_container'), self::PERFORMANCE_PROJECT_NAME, 'Test Fixture Performance Project');

		$casts = self::container('casts_container', $p['project'], 'besetzungen', 'Besetzungen');
		$p['cast_a'] = self::newPage('cast', $casts, 'test-fixture-cast-a', 'Test Cast A');
		$p['cast_b'] = self::newPage('cast', $casts, 'test-fixture-cast-b', 'Test Cast B');

		$seasons = self::container('seasons_container', $p['project'], 'staffeln', 'Staffeln');
		$p['season_1'] = self::newPage('season', $seasons, 'test-fixture-season-1', 'Test Season 1');
		$p['season_2'] = self::newPage('season', $seasons, 'test-fixture-season-2', 'Test Season 2');

		$portraits = self::container('portraits_container', $p['project'], 'portraits', 'Portraits');
		$p['portrait_a'] = self::newPage('portrait', $portraits, 'test-fixture-portrait-a', 'Test Portrait A');
		$p['portrait_b'] = self::newPage('portrait', $portraits, 'test-fixture-portrait-b', 'Test Portrait B');
		$p['portrait_c'] = self::newPage('portrait', $portraits, 'test-fixture-portrait-c', 'Test Portrait C');
		$p['portrait_d'] = self::newPage('portrait', $portraits, 'test-fixture-portrait-d', 'Test Portrait D');

		$roles = self::container('project_roles_container', $p['project'], 'rollen', 'Rollen');
		$p['roles_container'] = $roles;
		$p['role_mixed'] = self::newPage('project_role', $roles, 'test-fixture-role-mixed', 'Test Role Mixed');
		self::addParticipant($p['role_mixed'], 'cast', ['casts' => $p['cast_a'], 'portraits' => $p['portrait_a']]);
		self::addParticipant($p['role_mixed'], 'cast', ['casts' => $p['cast_b'], 'portraits' => $p['portrait_b']]);
		self::addParticipant($p['role_mixed'], 'portraits', ['portraits' => $p['portrait_c']]);
		$p['role_cast_b'] = self::newPage('project_role', $roles, 'test-fixture-role-cast-b', 'Test Role Cast B');
		self::addParticipant($p['role_cast_b'], 'cast', ['casts' => $p['cast_b'], 'portraits' => $p['portrait_b']]);
		$p['role_other_season'] = self::newPage('project_role', $roles, 'test-fixture-role-other-season', 'Test Role Other Season');
		self::addParticipant($p['role_other_season'], 'season', ['seasons' => $p['season_2'], 'portraits' => $p['portrait_d']]);

		$p['location'] = self::newPage('location', $pages->get('template=locations_container'), self::PERFORMANCE_LOCATION_NAME, 'Test Location', [
			'address' => '<p>Test Street 1</p>',
			'directions' => '<p>Test directions</p>',
		]);

		$events = self::container('events_container', $p['project'], 'termine', 'Termine');
		$p['event'] = self::newPage('event', $events, 'test-fixture-event', 'Test Event', [
			'seasons' => $p['season_1'],
			'admission_minutes' => 45,
			'visitor_info' => '<p>Event visitor info</p>',
			'datetime_from' => 1893484800,
			'datetime_until' => 1893495600,
		]);

		$category = $pages->get('template.name=event_category, name=auffuehrung, include=all');

		$p['period_cast_a'] = self::newPage('time_period', $p['event'], 'test-fixture-period-cast-a', 'Test Period Cast A', [
			'datetime_from' => 1893484800,
			'datetime_until' => 1893495600,
			'accessable_for_guests' => 1,
			'casts' => $p['cast_a'],
			'location' => $p['location'],
			'event_categories' => $category,
			'link' => 'https://tickets.example.org/test',
			'description_text' => '<p>Period description</p>',
			'admission_minutes' => 20,
			'visitor_info' => '<p>Period visitor info</p>',
		]);
		$p['period_no_casts'] = self::newPage('time_period', $p['event'], 'test-fixture-period-no-casts', 'Test Period No Casts', [
			'datetime_from' => 1893488400,
			'accessable_for_guests' => 1,
		]);
		$p['period_not_for_guests'] = self::newPage('time_period', $p['event'], 'test-fixture-period-not-for-guests', 'Test Period Not For Guests', [
			'datetime_from' => 1893492000,
			'accessable_for_guests' => 0,
		]);
		$p['period_unpublished'] = self::newPage('time_period', $p['event'], 'test-fixture-period-unpublished', 'Test Period Unpublished', [
			'datetime_from' => 1893495600,
			'accessable_for_guests' => 1,
		], true);

		$p['period_not_released'] = self::newPage('time_period', $p['event'], 'test-fixture-period-not-released', 'Test Period Not Released', [
			'datetime_from' => 1893499200,
			'accessable_for_guests' => 1,
			'releasetime_start_activate' => 1,
			'releasetime_start' => 4102444800,
		]);
		$p['period_for_trash'] = self::newPage('time_period', $p['event'], 'test-fixture-period-for-trash', 'Test Period For Trash', [
			'datetime_from' => 1893502800,
			'accessable_for_guests' => 1,
		]);

		$p['event']->of(false);
		foreach (['period_cast_a', 'period_no_casts', 'period_not_for_guests', 'period_unpublished', 'period_not_released', 'period_for_trash'] as $key) {
			$p['event']->time_periods->add($p[$key]);
		}
		$pages->save($p['event'], ['quiet' => true]);

		return $p;
	}

	/**
	 * Removes the pages of createPerformanceTree(), also leftovers of an
	 * aborted earlier run.
	 */
	public static function deletePerformanceTree(): void {
		$pages = wire('pages');
		foreach ([
			'template=project, name=' . self::PERFORMANCE_PROJECT_NAME . ', include=all',
			'template=location, name=' . self::PERFORMANCE_LOCATION_NAME . ', include=all',
			// Saving a project creates a tag page with the project's name.
			'template=tag, name=' . self::PERFORMANCE_PROJECT_NAME . ', include=all',
		] as $selector) {
			foreach ($pages->find($selector) as $page) {
				$pages->delete($page, true);
			}
		}
	}

	public const NEXT_PROJECT_NAME = 'test-fixture-next-project';
	public const NEXT_OTHER_PROJECT_NAME = 'test-fixture-next-other-project';

	/**
	 * Creates two projects with performances for `performances/next`, with
	 * times relative to $base. Returns the created pages by key. Remove them
	 * again with deleteNextPerformanceTree().
	 *
	 * Project "next" (event with 45 admission minutes):
	 * - next_main: visible, starts at $base + 2 h; tests move it
	 * - next_later: visible, starts at $base + 2 days
	 * - next_not_for_guests, next_unpublished, next_not_released,
	 *   next_other_category: hidden or no performance, all start at $base + 30 min
	 *   (inside the admission time of the event)
	 *
	 * Project "next other":
	 * - other_soon: visible, starts at $base + 1 h
	 *
	 * @return array<string, Page>
	 */
	public static function createNextPerformanceTree(int $base): array {
		self::deleteNextPerformanceTree();

		$pages = wire('pages');
		$p = [];
		$performance = $pages->get('template.name=event_category, name=auffuehrung, include=all');
		$otherCategory = $pages->get('template.name=event_category, name=generalprobe, include=all');
		if (!$performance->id || !$otherCategory->id) {
			throw new \RuntimeException('Event categories "auffuehrung" and "generalprobe" are required.');
		}

		$p['project'] = self::newPage('project', $pages->get('template=projects_container'), self::NEXT_PROJECT_NAME, 'Test Fixture Next Project');
		$events = self::container('events_container', $p['project'], 'termine', 'Termine');
		$p['event'] = self::newPage('event', $events, 'test-fixture-next-event', 'Test Next Event', [
			'admission_minutes' => 45,
			'datetime_from' => $base,
		]);

		$periods = [
			'next_main' => ['datetime_from' => $base + 7200, 'datetime_until' => $base + 14400],
			'next_later' => ['datetime_from' => $base + 172800],
			'next_not_for_guests' => ['datetime_from' => $base + 1800, 'accessable_for_guests' => 0],
			'next_unpublished' => ['datetime_from' => $base + 1800],
			'next_not_released' => ['datetime_from' => $base + 1800, 'releasetime_start_activate' => 1, 'releasetime_start' => 4102444800],
			'next_other_category' => ['datetime_from' => $base + 1800, 'event_categories' => $otherCategory],
		];
		foreach ($periods as $key => $values) {
			$p[$key] = self::newPage('time_period', $p['event'], 'test-fixture-' . str_replace('_', '-', $key), 'Test ' . $key, array_merge([
				'accessable_for_guests' => 1,
				'event_categories' => $performance,
			], $values), $key === 'next_unpublished');
		}

		$p['event']->of(false);
		foreach (array_keys($periods) as $key) {
			$p['event']->time_periods->add($p[$key]);
		}
		$pages->save($p['event'], ['quiet' => true]);

		$p['other_project'] = self::newPage('project', $pages->get('template=projects_container'), self::NEXT_OTHER_PROJECT_NAME, 'Test Fixture Next Other Project');
		$otherEvents = self::container('events_container', $p['other_project'], 'termine', 'Termine');
		$p['other_event'] = self::newPage('event', $otherEvents, 'test-fixture-next-other-event', 'Test Next Other Event', [
			'datetime_from' => $base + 3600,
		]);
		$p['other_soon'] = self::newPage('time_period', $p['other_event'], 'test-fixture-other-soon', 'Test other_soon', [
			'datetime_from' => $base + 3600,
			'accessable_for_guests' => 1,
			'event_categories' => $performance,
		]);
		$p['other_event']->of(false);
		$p['other_event']->time_periods->add($p['other_soon']);
		$pages->save($p['other_event'], ['quiet' => true]);

		return $p;
	}

	/**
	 * Sets the times of a performance. A $until of null clears the end.
	 */
	public static function setPerformanceTimes(Page $period, int $from, ?int $until): void {
		$period->of(false);
		$period->set('datetime_from', $from);
		$period->set('datetime_until', $until ?? '');
		wire('pages')->save($period, ['quiet' => true]);
	}

	/**
	 * Removes the pages of createNextPerformanceTree(), also leftovers of an
	 * aborted earlier run.
	 */
	public static function deleteNextPerformanceTree(): void {
		$pages = wire('pages');
		foreach ([self::NEXT_PROJECT_NAME, self::NEXT_OTHER_PROJECT_NAME] as $name) {
			// Saving a project creates a tag page with the project's name.
			foreach ($pages->find('template=project|tag, name=' . $name . ', include=all') as $page) {
				$pages->delete($page, true);
			}
		}
	}

	private static function newPage(string $template, Page $parent, string $name, string $title, array $values = [], bool $unpublished = false): Page {
		if (!$parent->id) {
			throw new \RuntimeException('Parent page for ' . $name . ' not found.');
		}

		$page = new Page();
		$page->template = $template;
		$page->parent = $parent;
		$page->name = $name;
		$page->title = $title;
		$page->of(false);
		foreach ($values as $field => $value) {
			if (!$page->template->hasField($field)) {
				throw new \RuntimeException('Field ' . $field . ' is missing on template ' . $template . '.');
			}
			$page->set($field, $value);
		}
		if ($unpublished) {
			$page->addStatus(Page::statusUnpublished);
		}
		wire('pages')->save($page, ['quiet' => true]);

		return $page;
	}

	/**
	 * Returns the container below the project, creating it if the project
	 * did not get one on creation.
	 */
	private static function container(string $template, Page $parent, string $name, string $title): Page {
		$existing = $parent->child('template=' . $template . ', include=all');
		if ($existing->id) {
			return $existing;
		}

		return self::newPage($template, $parent, $name, $title);
	}

	private static function addParticipant(Page $role, string $matrixType, array $values): void {
		$role->of(false);
		$item = $role->participants->getNew();
		$item->setMatrixType($matrixType);
		foreach ($values as $field => $value) {
			$item->set($field, $value);
		}
		$item->save();
		wire('pages')->save($role, ['quiet' => true]);
	}
}
