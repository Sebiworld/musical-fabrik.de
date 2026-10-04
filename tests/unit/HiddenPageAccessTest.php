<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\ForbiddenException;
use ProcessWire\NotFoundException;
use ProcessWire\Page;
use ProcessWire\PageListApi;
use ProcessWire\ProjectApi;
use ProcessWire\ProjectRolesApi;
use ProcessWire\TwackApiAccess;
use ProcessWire\User;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * Route handlers for pages a user may or may not view, called in-process
 * with a chosen current user. Guests get a 404 for a page they may not view
 * (see the api suite); a user who may view it still gets the page.
 */
final class HiddenPageAccessTest extends TestCase {
	private static ?array $portraits = null;
	private static ?Page $project = null;
	private bool $hideInaccessiblePages;

	public static function setUpBeforeClass(): void {
		$api = wire('config')->paths->site . 'api/';
		require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';
		require_once $api . 'ProjectApi.class.php';
		require_once $api . 'ProjectRolesApi.class.php';
		require_once $api . 'PageListApi.class.php';
	}

	protected function setUp(): void {
		$this->hideInaccessiblePages = TwackApiAccess::$hideInaccessiblePages;
	}

	protected function tearDown(): void {
		TwackApiAccess::$hideInaccessiblePages = $this->hideInaccessiblePages;
		wire('twack')->disableAjaxResponse();
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
	}

	public static function tearDownAfterClass(): void {
		if (self::$portraits !== null) {
			Fixtures::deletePortraitTree();
			self::$portraits = null;
		}
		if (self::$project !== null) {
			Fixtures::deleteUnpublishedProject();
			self::$project = null;
		}
	}

	public function testASuperuserStillGetsAnUnpublishedProject(): void {
		self::$project = Fixtures::createUnpublishedProject();
		self::assertTrue(self::$project->isUnpublished(), 'The test project is expected to be unpublished.');
		$this->actAs($this->superuser());
		$data = (object) ['id' => self::$project->id];

		self::assertSame(self::$project->id, ProjectApi::getProjectDetail(clone $data)['id']);
		self::assertIsArray(ProjectRolesApi::getProjectRoles(clone $data));
		self::assertIsArray(PageListApi::getPageListItems(clone $data));

		TwackApiAccess::$hideInaccessiblePages = true;
		self::assertSame(self::$project->id, TwackApiAccess::pageIDRequest(clone $data)['id']);
	}

	public function testASuperuserStillGetsAnUnpublishedPage(): void {
		$unpublished = Fixtures::page(Fixtures::UNPUBLISHED_PATH);
		self::assertTrue($unpublished->isUnpublished(), 'The seed page is expected to be unpublished.');
		$this->actAs($this->superuser());
		$data = (object) ['id' => $unpublished->id];

		self::assertIsArray(PageListApi::getPageListItems(clone $data));

		TwackApiAccess::$hideInaccessiblePages = true;
		self::assertSame($unpublished->id, TwackApiAccess::pageIDRequest(clone $data)['id']);
		self::assertSame($unpublished->id, TwackApiAccess::pagePathRequest((object) ['path' => trim(Fixtures::UNPUBLISHED_PATH, '/')])['id']);
	}

	public function testAGuestGetsNotFoundForAnUnpublishedPage(): void {
		$unpublished = Fixtures::page(Fixtures::UNPUBLISHED_PATH);
		$this->actAs(wire('users')->getGuestUser());

		foreach ([
			'projects' => fn () => ProjectApi::getProjectDetail((object) ['id' => $unpublished->id]),
			'project-roles' => fn () => ProjectRolesApi::getProjectRoles((object) ['id' => $unpublished->id]),
			'page-list/items' => fn () => PageListApi::getPageListItems((object) ['id' => $unpublished->id]),
		] as $route => $call) {
			try {
				$call();
				self::fail($route . ': expected a NotFoundException.');
			} catch (NotFoundException $e) {
				self::assertSame(404, $e->getCode(), $route);
			}
		}
	}

	public function testTwackKeepsAnswering403UnlessHidingIsEnabled(): void {
		$unpublished = Fixtures::page(Fixtures::UNPUBLISHED_PATH);
		$this->actAs(wire('users')->getGuestUser());

		TwackApiAccess::$hideInaccessiblePages = false;
		try {
			TwackApiAccess::pageIDRequest((object) ['id' => $unpublished->id]);
			self::fail('Expected a ForbiddenException.');
		} catch (ForbiddenException $e) {
			self::assertSame(403, $e->getCode());
		}

		TwackApiAccess::$hideInaccessiblePages = true;
		$this->expectException(NotFoundException::class);
		TwackApiAccess::pageIDRequest((object) ['id' => $unpublished->id]);
	}

	public function testASuperuserGetsPortraitsOutsideTheirReleaseTime(): void {
		self::$portraits = Fixtures::createPortraitTree();
		$this->actAs($this->superuser());
		$service = wire('modules')->get('Twack')->getService('ProjectRolesService');

		$ids = array_map(fn ($page) => $page->id, self::$portraits);
		$result = $service->getProjectPortraits($ids);

		$expected = [self::$portraits['visible']->id, self::$portraits['not_released']->id, self::$portraits['expired']->id];
		sort($expected);
		$actual = array_map('intval', array_keys($result['portraits']));
		sort($actual);
		self::assertSame($expected, $actual);
	}

	private function actAs(User $user): void {
		wire('users')->setCurrentUser($user);
	}

	private function superuser(): User {
		$user = wire('users')->get('roles=superuser, sort=id');
		if (!$user instanceof User || !$user->id || !$user->isSuperuser()) {
			self::markTestSkipped('The test database has no superuser.');
		}

		return $user;
	}
}
