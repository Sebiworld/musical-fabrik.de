<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
use ProcessWire\Page;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * The sitemap and the link preview follow the same visibility rule
 * (PublicVisibilityService): a page or performance is left out as soon as it
 * or a parent is unpublished, locked by a password, not released or locked by
 * permissions. Hidden parents and parents without an output of their own do
 * not count.
 */
final class PublicVisibilityTest extends TestCase {
	/** @var array<string, Page> */
	private static array $pages = [];

	/** @var array<string, Page> */
	private static array $performancePages = [];

	private mixed $noindex;
	private array $fieldDefaults = [];
	private ?string $hookId = null;

	public static function setUpBeforeClass(): void {
		require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';
		self::$pages = Fixtures::createLinkPreviewTree();
		self::$performancePages = Fixtures::createPerformanceTree();
	}

	public static function tearDownAfterClass(): void {
		Fixtures::deleteLinkPreviewTree();
		Fixtures::deletePerformanceTree();
		self::$pages = [];
		self::$performancePages = [];
	}

	protected function setUp(): void {
		// Every page into the sitemap unless it says otherwise, changed in
		// memory only: the test database runs with $config->noindex and field
		// defaults that leave all pages out.
		$this->noindex = wire('config')->noindex;
		wire('config')->noindex = false;
		$field = wire('fields')->get('seo');
		$this->fieldDefaults = [$field->get('sitemap_include'), $field->get('robots_noIndex')];
		$field->set('sitemap_include', 1);
		$field->set('robots_noIndex', 0);
		$this->hookId = wire()->addHookAfter('SeoMaestro::renderSeoDataValue', function (HookEvent $event) {
			if ($event->arguments(0) === 'sitemap' && $event->arguments(1) === 'include') {
				$event->return = (int) $event->arguments(2);
			}
		}, ['priority' => 1000]);
	}

	protected function tearDown(): void {
		wire()->removeHook($this->hookId);
		$field = wire('fields')->get('seo');
		$field->set('sitemap_include', $this->fieldDefaults[0]);
		$field->set('robots_noIndex', $this->fieldDefaults[1]);
		wire('config')->noindex = $this->noindex;
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
		wire('twack')->disableAjaxResponse();
	}

	public function testSitemapAndPreviewAgreeOnPagesBelowParents(): void {
		$cases = [
			'below an unpublished parent' => [$this->path('role_child'), false],
			'below a password protected parent' => [$this->path('password_child'), false],
			'below a hidden parent' => [$this->path('hidden_child'), true],
			'project below a parent without output' => [Fixtures::PROJECT_PATH, true],
			'page below the home page' => [$this->path('special'), true],
		];
		self::assertFalse(wire('pages')->get('template=projects_container')->viewable(), 'The projects container is expected to have no output.');
		self::assertTrue(wire('pages')->get('id=' . self::$pages['role_child']->id . ', include=all')->viewable(), 'The role below the unpublished role is expected to be viewable.');

		$xml = $this->generateSitemapAsSuperuser();
		$preview = wire('modules')->get('Twack')->getService('LinkPreviewService');

		foreach ($cases as $case => [$path, $public]) {
			self::assertSame($public, str_contains($xml, $path . '</loc>'), 'sitemap: ' . $case);
			self::assertSame($public, is_array($preview->getPreview($path)), 'preview: ' . $case);
		}
	}

	public function testPerformanceBelowAnUnpublishedContainerIsLeftOut(): void {
		$p = self::$performancePages;
		$seo = wire('modules')->get('Twack')->getService('SeoService');
		$preview = wire('modules')->get('Twack')->getService('LinkPreviewService');
		$url = $seo->getPerformanceUrl($p['period_cast_a']);
		$path = $p['project']->path . 'vorstellungen/' . $p['period_cast_a']->id . '/';
		$this->setProjectSitemap($p['project']);
		$events = $p['event']->parent;

		$published = [$this->performanceLocs($seo), $preview->getPreview($path)];
		$events->of(false);
		$events->addStatus(Page::statusUnpublished);
		wire('pages')->save($events, ['quiet' => true]);
		try {
			$unpublished = [$this->performanceLocs($seo), $preview->getPreview($path)];
		} finally {
			$events->removeStatus(Page::statusUnpublished);
			wire('pages')->save($events, ['quiet' => true]);
		}

		self::assertContains($url, $published[0]);
		self::assertIsArray($published[1]);
		self::assertNotContains($url, $unpublished[0]);
		self::assertNull($unpublished[1]);
	}

	public function testSeoNoindexFollowsTheRule(): void {
		$seo = wire('modules')->get('Twack')->getService('SeoService');
		$cases = [
			'locked by password' => [$this->page('password'), true],
			'below an unpublished parent' => [$this->page('role_child'), true],
			'below a hidden parent' => [$this->page('hidden_child'), false],
			'project below a parent without output' => [Fixtures::page(Fixtures::PROJECT_PATH), false],
			'page below the home page' => [$this->page('special'), false],
		];
		$annie = wire('pages')->get('/projekte/annie/');
		if ($annie->id) {
			$cases['Annie'] = [$annie, false];
		}

		foreach ($cases as $case => [$page, $noindex]) {
			self::assertTrue($page->viewable(), $case . ': the route is expected to deliver the page to guests.');
			self::assertSame($noindex, $seo->getSeoAjax($page)['noindex'], $case);
		}
	}

	public function testPerformanceSeoNoindexFollowsTheRule(): void {
		$p = self::$performancePages;
		$seo = wire('modules')->get('Twack')->getService('SeoService');
		$events = $p['event']->parent;

		$published = $seo->getSeoAjax(wire('pages')->get($p['period_cast_a']->id))['noindex'];
		$events->of(false);
		$events->addStatus(Page::statusUnpublished);
		wire('pages')->save($events, ['quiet' => true]);
		try {
			$unpublished = $seo->getSeoAjax(wire('pages')->get($p['period_cast_a']->id))['noindex'];
		} finally {
			$events->removeStatus(Page::statusUnpublished);
			wire('pages')->save($events, ['quiet' => true]);
		}

		self::assertFalse($published);
		self::assertTrue($unpublished);
	}

	public function testTheCurrentUserIsRestored(): void {
		$superuser = $this->superuser();
		wire('users')->setCurrentUser($superuser);

		$public = wire('modules')->get('Twack')->getService('PublicVisibilityService')->isPublicPage(Fixtures::page(Fixtures::UNPUBLISHED_PATH));

		self::assertFalse($public);
		self::assertSame($superuser->id, wire('user')->id);
	}

	private function generateSitemapAsSuperuser(): string {
		$file = sys_get_temp_dir() . '/mf-test-sitemap-' . getmypid() . '.xml';
		wire('users')->setCurrentUser($this->superuser());

		try {
			wire('modules')->get('SeoMaestro')->getSitemapManager()->generate($file);

			return is_file($file) ? (string) file_get_contents($file) : '';
		} finally {
			@unlink($file);
			wire('users')->setCurrentUser(wire('users')->getGuestUser());
		}
	}

	/**
	 * @return string[]
	 */
	private function performanceLocs(object $seo): array {
		return array_map(fn ($item) => $item->loc, $seo->getPerformanceSitemapItems());
	}

	private function setProjectSitemap(Page $project): void {
		$project->of(false);
		$project->seo->set('sitemap_include', '1');
		$project->seo->set('robots_noIndex', '0');
		$project->trackChange('seo');
		wire('pages')->save($project, ['quiet' => true]);
	}

	private function page(string $key): Page {
		return wire('pages')->get('id=' . self::$pages[$key]->id . ', include=all');
	}

	private function path(string $key): string {
		return wire('pages')->get('id=' . self::$pages[$key]->id . ', include=all')->path;
	}

	private function superuser(): Page {
		$superuser = wire('users')->get('roles=superuser, sort=id');
		self::assertTrue($superuser->id > 0, 'The test needs a superuser.');

		return $superuser;
	}
}
