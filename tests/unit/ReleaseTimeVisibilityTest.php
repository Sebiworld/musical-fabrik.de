<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
use ProcessWire\Page;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * A project in preparation (release time in the future) and the pages below
 * it stay invisible in everything built for guests, also when a superuser
 * (who may view pages that are not released) triggers it.
 */
final class ReleaseTimeVisibilityTest extends TestCase {
	/** @var array<string, Page> */
	private static array $pages = [];

	private mixed $noindex;

	public static function setUpBeforeClass(): void {
		require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';
		self::$pages = Fixtures::createReleaseTree();
	}

	public static function tearDownAfterClass(): void {
		Fixtures::deleteReleaseTree();
		self::$pages = [];
	}

	protected function setUp(): void {
		$this->noindex = wire('config')->noindex;
	}

	protected function tearDown(): void {
		wire('config')->noindex = $this->noindex;
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
		wire('twack')->disableAjaxResponse();
	}

	public function testTheTreeIsHiddenFromGuestsButVisibleToASuperuser(): void {
		foreach (['project', 'info', 'article', 'gallery'] as $key) {
			self::assertFalse($this->page($key)->viewable(), $key . ' as guest');
		}
		self::assertTrue($this->page('control')->viewable(), 'control as guest');

		wire('users')->setCurrentUser($this->superuser());
		foreach (['project', 'info', 'article', 'gallery'] as $key) {
			self::assertTrue($this->page($key)->viewable(), $key . ' as superuser');
		}
	}

	public function testPerformanceOfAnUnreleasedProjectIsOnlyPublicForASuperuser(): void {
		$performances = wire('modules')->get('Twack')->getService('PerformancesService');
		$id = self::$pages['period']->id;

		$asGuest = $performances->getPublicPerformancePage($id);
		wire('users')->setCurrentUser($this->superuser());
		$asSuperuser = $performances->getPublicPerformancePage($id);

		self::assertNull($asGuest);
		self::assertInstanceOf(Page::class, $asSuperuser);
	}

	public function testPerformanceSitemapItemsOfASuperuserLeaveOutTheUnreleasedProject(): void {
		wire('config')->noindex = false;
		$seo = wire('modules')->get('Twack')->getService('SeoService');
		$url = $seo->getPerformanceUrl(self::$pages['period']);
		wire('users')->setCurrentUser($this->superuser());

		$locs = array_map(fn ($item) => $item->loc, $seo->getPerformanceSitemapItems());
		$this->setProjectReleased(true);
		try {
			$releasedLocs = array_map(fn ($item) => $item->loc, $seo->getPerformanceSitemapItems());
		} finally {
			$this->setProjectReleased(false);
		}

		self::assertNotContains($url, $locs);
		self::assertContains($url, $releasedLocs, 'Once released, the performance is expected in the sitemap.');
	}

	public function testSitemapGeneratedByASuperuserLeavesOutUnreleasedPages(): void {
		wire('config')->noindex = false;
		$superuser = $this->superuser();
		// Servers with $config->noindex leave every page out of the sitemap;
		// undone here, so that the released control page is listed.
		$hookId = wire()->addHookAfter('SeoMaestro::renderSeoDataValue', function (HookEvent $event) {
			if ($event->arguments(0) === 'sitemap' && $event->arguments(1) === 'include') {
				$event->return = (int) $event->arguments(2);
			}
		}, ['priority' => 1000]);
		$file = sys_get_temp_dir() . '/mf-test-sitemap-' . getmypid() . '.xml';
		wire('users')->setCurrentUser($superuser);

		try {
			wire('modules')->get('SeoMaestro')->getSitemapManager()->generate($file);
			$xml = is_file($file) ? (string) file_get_contents($file) : '';
			$userAfter = wire('user')->id;
			@unlink($file);

			$this->setProjectReleased(true);
			wire('modules')->get('SeoMaestro')->getSitemapManager()->generate($file);
			$releasedXml = is_file($file) ? (string) file_get_contents($file) : '';
		} finally {
			$this->setProjectReleased(false);
			wire()->removeHook($hookId);
			@unlink($file);
		}

		self::assertSame($superuser->id, $userAfter, 'The current user must be restored.');
		self::assertStringContainsString(self::$pages['control']->path . '</loc>', $xml);
		self::assertStringNotContainsString(Fixtures::RELEASE_PREFIX . 'project', $xml);
		// Once released, the project, its pages and its performance are listed.
		self::assertStringContainsString($this->page('project')->path . '</loc>', $releasedXml);
		self::assertStringContainsString($this->page('info')->path . '</loc>', $releasedXml);
		self::assertStringContainsString($this->page('project')->path . 'vorstellungen/' . self::$pages['period']->id . '/</loc>', $releasedXml);
	}

	public function testLinkPreviewOfASuperuserIsNotFoundForTheUnreleasedTree(): void {
		$preview = wire('modules')->get('Twack')->getService('LinkPreviewService');
		$paths = [];
		foreach (['project', 'info', 'article', 'gallery'] as $key) {
			$paths[$key] = $this->page($key)->path;
		}
		$paths['performance'] = $this->page('project')->path . 'vorstellungen/' . self::$pages['period']->id . '/';

		wire('users')->setCurrentUser($this->superuser());
		foreach ($paths as $key => $path) {
			self::assertNull($preview->getPreview($path), $key);
		}
		self::assertIsArray($preview->getPreview($this->page('control')->path), 'control');
	}

	public function testNoindexHooksDoNotChangeVisibility(): void {
		if (wire('config')->noindex !== true) {
			self::markTestSkipped('The noindex hooks of site/ready.php are only active with $config->noindex.');
		}

		self::assertFalse($this->page('project')->viewable());
		self::assertTrue($this->page('control')->viewable());
		wire('users')->setCurrentUser($this->superuser());
		self::assertTrue($this->page('project')->viewable());
	}

	private function setProjectReleased(bool $released): void {
		$project = $this->page('project');
		$project->of(false);
		$project->releasetime_start_activate = $released ? 0 : 1;
		wire('pages')->save($project, ['quiet' => true]);
	}

	private function page(string $key): Page {
		return wire('pages')->get('id=' . self::$pages[$key]->id . ', include=all');
	}

	private function superuser(): Page {
		$superuser = wire('users')->get('roles=superuser, sort=id');
		self::assertTrue($superuser->id > 0, 'The test needs a superuser.');

		return $superuser;
	}
}
