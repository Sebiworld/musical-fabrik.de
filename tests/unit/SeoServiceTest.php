<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
use ProcessWire\Page;
use ProcessWire\TwackApiAccess;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * SeoService: the `seo` object of pages and performances, built from the
 * SeoMaestro field `seo` with the configuration page as default.
 */
final class SeoServiceTest extends TestCase {
	private const KEYS = ['title', 'description', 'canonical', 'image', 'noindex'];

	/** @var array<string, Page> */
	private static array $pages = [];

	/** @var array<string, Page> */
	private static array $performancePages = [];

	private object $service;
	private mixed $noindex;

	public static function setUpBeforeClass(): void {
		require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';
		self::$pages = Fixtures::createSeoTree();
		self::$performancePages = Fixtures::createPerformanceTree();
		// The field defaults of the test database leave pages out of the sitemap.
		self::setProjectSitemap(1, 0);
	}

	public static function tearDownAfterClass(): void {
		Fixtures::deleteSeoTree();
		Fixtures::deletePerformanceTree();
		self::$pages = [];
		self::$performancePages = [];
	}

	protected function setUp(): void {
		$this->service = wire('modules')->get('Twack')->getService('SeoService');
		$this->noindex = wire('config')->noindex;
	}

	protected function tearDown(): void {
		wire('config')->noindex = $this->noindex;
		wire('twack')->disableAjaxResponse();
	}

	public function testOutputHasTheContractKeys(): void {
		$seo = $this->service->getSeoAjax(self::$pages['plain']);

		self::assertSame(self::KEYS, array_keys($seo));
		self::assertIsString($seo['title']);
		self::assertIsString($seo['description']);
		self::assertIsString($seo['canonical']);
		self::assertIsString($seo['image']);
		self::assertIsBool($seo['noindex']);
	}

	public function testMaintainedMetaValuesWin(): void {
		$seo = $this->service->getSeoAjax(self::$pages['maintained']);

		self::assertSame($this->formatTitle('Tom & Jerry „Grüße“'), $seo['title']);
		self::assertSame('Eigene Beschreibung & mehr', $seo['description']);
	}

	public function testPageWithoutMaintenanceUsesTheDefaults(): void {
		$plain = $this->service->getSeoAjax(self::$pages['plain']);
		$noImage = $this->service->getSeoAjax(self::$pages['no_image']);

		self::assertSame($this->formatTitle('Test Fixture SEO Plain'), $plain['title']);
		self::assertSame($this->formatTitle('Test Fixture SEO No Image'), $noImage['title']);
		self::assertNotSame('', $noImage['description']);
	}

	public function testDescriptionIsPlainText(): void {
		$seo = $this->service->getSeoAjax(self::$pages['plain']);

		self::assertSame('Erster Satz & zweiter Satz.', $seo['description']);
	}

	public function testDefaultDescriptionIsPlainText(): void {
		$seo = $this->service->getSeoAjax(self::$pages['no_image']);

		self::assertDoesNotMatchRegularExpression('/[<>]|&[a-z#0-9]+;|\x{00A0}/u', $seo['description']);
	}

	public function testCanonicalIsTheFrontendUrlWithTrailingSlash(): void {
		$base = $this->frontendBaseUrl();

		self::assertSame($base . '/' . Fixtures::SEO_PLAIN_NAME . '/', $this->service->getSeoAjax(self::$pages['plain'])['canonical']);
		self::assertSame($base . '/', $this->service->getSeoAjax(wire('pages')->get('/'))['canonical']);
	}

	public function testMaintainedCanonicalLosesItsQueryAndGetsATrailingSlash(): void {
		$canonical = $this->service->getSeoAjax(self::$pages['maintained'])['canonical'];

		self::assertSame($this->frontendBaseUrl() . '/eigener-pfad/', $canonical);
	}

	public function testCanonicalNeverPointsToTheBackend(): void {
		foreach ([self::$pages['plain'], self::$pages['maintained'], wire('pages')->get('/'), self::$performancePages['period_cast_a']] as $page) {
			$canonical = $this->service->getSeoAjax($page)['canonical'];
			self::assertStringStartsWith('https://', $canonical);
			self::assertStringNotContainsString('/backend/', $canonical);
			self::assertStringNotContainsString('?', $canonical);
			self::assertStringEndsWith('/', $canonical);
		}
	}

	public function testImageIsAnAbsoluteUrlOfAnImageAbout1200PixelsWide(): void {
		$image = $this->service->getSeoAjax(self::$pages['plain'])['image'];

		self::assertIsString($image);
		self::assertSame(wire('config')->httpHost, parse_url($image, PHP_URL_HOST));
		self::assertStringContainsString(pathinfo(Fixtures::SEO_IMAGE, PATHINFO_FILENAME), basename($image));
		self::assertSame(1200, $this->imageWidth($image));
	}

	public function testPageWithoutImageGetsTheConfigurationImageOrNull(): void {
		$image = $this->service->getSeoAjax(self::$pages['no_image'])['image'];

		$configImages = wire('pages')->get('template.name=configuration')->getUnformatted('main_image');
		if (!$configImages || !$configImages->count()) {
			self::assertNull($image);

			return;
		}

		self::assertIsString($image);
		self::assertSame(wire('config')->httpHost, parse_url($image, PHP_URL_HOST));
		self::assertStringStartsWith(pathinfo($configImages->first()->basename, PATHINFO_FILENAME), basename($image));
		self::assertLessThanOrEqual(1200, $this->imageWidth($image));
	}

	public function testNoindexFromTheConfigurationAppliesToEveryPage(): void {
		wire('config')->noindex = true;

		self::assertTrue($this->service->getSeoAjax(self::$pages['plain'])['noindex']);
		self::assertTrue($this->service->getSeoAjax(self::$performancePages['period_cast_a'])['noindex']);
	}

	public function testWithoutConfigurationOnlyAPageOwnNoindexApplies(): void {
		wire('config')->noindex = false;

		self::assertFalse($this->service->getSeoAjax(self::$pages['plain'])['noindex']);
		// Inherits the default of the field, which is not used.
		self::assertFalse($this->service->getSeoAjax(self::$pages['no_image'])['noindex']);
		self::assertTrue($this->service->getSeoAjax(self::$pages['maintained'])['noindex']);
	}

	public function testNoindexServerRendersNoindexWithoutChangingThePageValues(): void {
		if ($this->noindex !== true) {
			self::markTestSkipped('The hook in site/ready.php is only registered when $config->noindex is true.');
		}

		$page = wire('pages')->get(self::$pages['plain']->id);

		self::assertSame(1, $page->seo->robots->noIndex);
		self::assertSame(1, $page->seo->robots->noFollow);
		self::assertSame(0, $page->seo->sitemap->include);
		self::assertSame(0, (int) $page->seo->robots->getUnformatted('noIndex'));
		self::assertSame(1, (int) $page->seo->sitemap->getUnformatted('include'));
	}

	public function testPageRequestWithNoindexLeavesTheFieldConfigurationUnchanged(): void {
		wire('config')->noindex = true;
		$field = wire('fields')->get('seo');
		$before = $this->storedFieldData($field->id);

		$saved = [];
		$hookId = wire('fields')->addHookBefore('save', function (HookEvent $event) use (&$saved) {
			$saved[] = $event->arguments(0)->name;
			// Never write the field configuration of the test database.
			$event->replace = true;
			$event->return = true;
		});

		try {
			$json = TwackApiAccess::pageIDRequest((object) ['id' => self::$pages['plain']->id]);
		} finally {
			wire('fields')->removeHook($hookId);
		}

		self::assertSame([], $saved, 'A page request must not save any field.');
		self::assertSame($before, $this->storedFieldData($field->id));
		self::assertTrue($json['seo']['noindex']);
		self::assertSame(self::KEYS, array_keys($json['seo']));
	}

	public function testPerformanceGetsTitleImageAndCanonicalFromItsProject(): void {
		$p = self::$performancePages;
		$seo = $this->service->getSeoAjax($p['period_cast_a']);

		self::assertSame(self::KEYS, array_keys($seo));
		self::assertSame($this->formatTitle('Test Period Cast A am Di., 1. Januar 2030 – Test Fixture Performance Project'), $seo['title']);
		self::assertSame('Period description', $seo['description']);
		self::assertSame(
			$this->frontendBaseUrl() . $p['project']->path . 'vorstellungen/' . $p['period_cast_a']->id . '/',
			$seo['canonical']
		);
	}

	/**
	 * @return array<string, array{0: int, 1: string}>
	 */
	public static function performanceDates(): array {
		return [
			'late evening in Berlin, same day in UTC' => [1794695400, 'Sa., 14. November 2026'],
			'after midnight in Berlin, day before in UTC' => [1794699000, 'So., 15. November 2026'],
			'month with umlaut' => [1803898800, 'Mo., 1. März 2027'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('performanceDates')]
	public function testPerformanceTitleNamesTheDateInGermanBerlinTime(int $timestamp, string $date): void {
		$period = self::$performancePages['period_for_trash'];
		Fixtures::setPerformanceTimes($period, $timestamp, null);

		$seo = $this->service->getSeoAjax(wire('pages')->get($period->id));

		self::assertSame($this->formatTitle('Test Period For Trash am ' . $date . ' – Test Fixture Performance Project'), $seo['title']);
	}

	public function testPerformanceWithoutDateHasNoDateInTheTitle(): void {
		$period = self::$performancePages['period_for_trash'];
		$period->of(false);
		$period->set('datetime_from', '');
		wire('pages')->save($period, ['quiet' => true]);

		$seo = $this->service->getSeoAjax(wire('pages')->get($period->id));

		self::assertSame($this->formatTitle('Test Period For Trash – Test Fixture Performance Project'), $seo['title']);
	}

	public function testHomePageTitleIsTheSiteName(): void {
		$expected = trim((string) wire('pages')->get('template.name=configuration')->seo_title);
		self::assertNotSame('', $expected, 'The test expects seo_title on the configuration page.');

		self::assertSame($expected, $this->service->getSeoAjax(wire('pages')->get('/'))['title']);
	}

	public function testHomePageWithOwnTitleKeepsIt(): void {
		$home = wire('pages')->get('/');
		$home->of(false);
		// Changed in memory only, never saved.
		$home->seo->meta->title = 'Eigener Startseitentitel';

		try {
			$title = $this->service->getSeoAjax($home)['title'];
		} finally {
			wire('pages')->uncache($home);
		}

		self::assertSame($this->formatTitle('Eigener Startseitentitel'), $title);
		self::assertSame('inherit', wire('pages')->get('/')->seo->get('meta_title'), 'The home page must stay unchanged.');
	}

	public function testPerformanceWithoutProjectImageHasNoError(): void {
		$p = self::$performancePages;
		self::assertSame(0, $p['project']->getUnformatted('main_image')->count(), 'The test project is expected to have no image.');

		$seo = $this->service->getSeoAjax($p['period_no_casts']);

		self::assertSame(self::KEYS, array_keys($seo));
		self::assertTrue($seo['image'] === null || is_string($seo['image']));
		self::assertNotSame('', $seo['description']);
	}

	public function testPerformanceImageComesFromTheProject(): void {
		$p = self::$performancePages;
		Fixtures::addGeneratedImage($p['project'], 'main_image', 'test-fixture-seo-project.jpg', 1600, 900);

		$image = $this->service->getSeoAjax(wire('pages')->get($p['period_cast_a']->id))['image'];

		self::assertIsString($image);
		self::assertStringStartsWith('test-fixture-seo-project', basename($image));
		self::assertSame(1200, $this->imageWidth($image));
	}

	public function testSitemapListsPublicPerformancesWithTheirCanonicalUrl(): void {
		// Test servers run with $config->noindex, which leaves performances out.
		wire('config')->noindex = false;
		$p = self::$performancePages;

		$items = $this->sitemapItemsByUrl();

		$canonical = $this->service->getSeoAjax($p['period_cast_a'])['canonical'];
		self::assertArrayHasKey($canonical, $items);
		self::assertSame(date('c', wire('pages')->get($p['period_cast_a']->id)->modified), $items[$canonical]->lastmod);
		self::assertArrayHasKey($this->service->getPerformanceUrl($p['period_no_casts']), $items);
		foreach (['period_not_for_guests', 'period_unpublished', 'period_not_released'] as $key) {
			self::assertArrayNotHasKey($this->service->getPerformanceUrl($p[$key]), $items, $key);
		}
	}

	public function testSitemapLeavesOutPerformancesOfAnUnpublishedProject(): void {
		// Test servers run with $config->noindex, which leaves performances out.
		wire('config')->noindex = false;
		$p = self::$performancePages;
		$url = $this->service->getPerformanceUrl($p['period_cast_a']);
		$project = $p['project'];
		$project->of(false);
		$project->addStatus(Page::statusUnpublished);
		wire('pages')->save($project, ['quiet' => true]);

		try {
			$items = $this->sitemapItemsByUrl();
		} finally {
			$project->removeStatus(Page::statusUnpublished);
			wire('pages')->save($project, ['quiet' => true]);
		}

		self::assertArrayNotHasKey($url, $items);
	}

	public function testSitemapChecksVisibilityAsGuestForALoggedInUser(): void {
		// Test servers run with $config->noindex, which leaves performances out.
		wire('config')->noindex = false;
		$p = self::$performancePages;
		$superuser = wire('users')->get('roles=superuser, sort=id');
		self::assertTrue($superuser->id > 0, 'The test needs a superuser.');
		wire('users')->setCurrentUser($superuser);

		try {
			$items = $this->sitemapItemsByUrl();
			self::assertSame($superuser->id, wire('user')->id, 'The current user must be restored.');
		} finally {
			wire('users')->setCurrentUser(wire('users')->getGuestUser());
		}

		self::assertArrayHasKey($this->service->getPerformanceUrl($p['period_cast_a']), $items);
		self::assertArrayNotHasKey($this->service->getPerformanceUrl($p['period_not_released']), $items);
	}

	public function testGeneratedSitemapContainsThePerformances(): void {
		// Test servers run with $config->noindex, which leaves performances out.
		wire('config')->noindex = false;
		$p = self::$performancePages;
		$file = sys_get_temp_dir() . '/mf-test-sitemap-' . getmypid() . '.xml';

		try {
			self::assertTrue(wire('modules')->get('SeoMaestro')->getSitemapManager()->generate($file));
			$xml = (string) file_get_contents($file);
		} finally {
			@unlink($file);
		}

		self::assertStringContainsString('<loc>' . $this->service->getPerformanceUrl($p['period_cast_a']) . '</loc>', $xml);
		self::assertStringNotContainsString('<loc>' . $this->service->getPerformanceUrl($p['period_not_released']) . '</loc>', $xml);
	}

	public function testNoindexServerAddsNoPerformancesToTheSitemap(): void {
		$url = $this->service->getPerformanceUrl(self::$performancePages['period_cast_a']);

		wire('config')->noindex = false;
		$withoutNoindex = $this->generateSitemap();
		wire('config')->noindex = true;
		$items = $this->service->getPerformanceSitemapItems();
		$withNoindex = $this->generateSitemap();

		self::assertStringContainsString('<loc>' . $url . '</loc>', $withoutNoindex);
		self::assertSame([], $items);
		self::assertStringNotContainsString('<loc>' . $url . '</loc>', $withNoindex);
	}

	public function testSitemapFollowsTheSitemapSettingsOfTheProject(): void {
		wire('config')->noindex = false;
		$url = $this->service->getPerformanceUrl(self::$performancePages['period_cast_a']);
		$field = wire('fields')->get('seo');
		$fieldDefaults = [$field->get('sitemap_include'), $field->get('robots_noIndex')];
		$results = [];

		try {
			foreach ([
				'included' => [1, 0, null],
				'not included' => [0, 0, null],
				'noindex' => [1, 1, null],
				'inherit, field includes' => ['inherit', 'inherit', [1, 0]],
				'inherit, field excludes' => ['inherit', 'inherit', [0, 0]],
				'inherit, field noindex' => ['inherit', 'inherit', [1, 1]],
			] as $case => [$include, $noIndex, $defaults]) {
				if ($defaults !== null) {
					// Changed in memory only, never saved.
					$field->set('sitemap_include', $defaults[0]);
					$field->set('robots_noIndex', $defaults[1]);
				}
				self::setProjectSitemap($include, $noIndex);
				$results[$case] = array_key_exists($url, $this->sitemapItemsByUrl());
				$field->set('sitemap_include', $fieldDefaults[0]);
				$field->set('robots_noIndex', $fieldDefaults[1]);
			}
		} finally {
			$field->set('sitemap_include', $fieldDefaults[0]);
			$field->set('robots_noIndex', $fieldDefaults[1]);
			self::setProjectSitemap(1, 0);
		}

		self::assertSame([
			'included' => true,
			'not included' => false,
			'noindex' => false,
			'inherit, field includes' => true,
			'inherit, field excludes' => false,
			'inherit, field noindex' => false,
		], $results);
	}

	/**
	 * Sets `sitemap_include` and `robots_noIndex` of the SeoMaestro field of
	 * the performance project (1, 0 or 'inherit').
	 */
	private static function setProjectSitemap(int|string $include, int|string $noIndex): void {
		$project = self::$performancePages['project'];
		$project->of(false);
		// Set on the field value, since the group setters store 'inherit' as 0.
		$project->seo->set('sitemap_include', (string) $include);
		$project->seo->set('robots_noIndex', (string) $noIndex);
		$project->trackChange('seo');
		wire('pages')->save($project, ['quiet' => true]);
	}

	private function generateSitemap(): string {
		$file = sys_get_temp_dir() . '/mf-test-sitemap-' . getmypid() . '.xml';

		try {
			// Without any entry SeoMaestro writes no file and returns false.
			wire('modules')->get('SeoMaestro')->getSitemapManager()->generate($file);

			return is_file($file) ? (string) file_get_contents($file) : '';
		} finally {
			@unlink($file);
		}
	}

	public function testPerformancesAreAppendedWithoutChangingTheEntriesOfSeoMaestro(): void {
		// Test servers run with $config->noindex, which leaves performances out.
		wire('config')->noindex = false;
		$p = self::$performancePages;
		$plainUrl = rtrim((string) wire('modules')->getConfig('SeoMaestro', 'baseUrl'), '/') . self::$pages['plain']->url;
		$original = null;
		$hooks = [
			// Servers with $config->noindex leave every page out of the sitemap;
			// undone here so that the entry of the plain test page is built.
			wire()->addHookAfter('SeoMaestro::renderSeoDataValue', function (HookEvent $event) {
				if ($event->arguments(0) === 'sitemap' && $event->arguments(1) === 'include') {
					$event->return = (int) $event->arguments(2);
				}
			}, ['priority' => 1000]),
			// Runs before the hook in site/ready.php.
			wire()->addHookAfter('SeoMaestro::sitemapItems', function (HookEvent $event) use (&$original) {
				$original = array_map(fn ($item) => [$item->loc, $item->lastmod, (string) $item->priority, $item->changefreq], $event->return);
			}, ['priority' => 10]),
		];
		$file = sys_get_temp_dir() . '/mf-test-sitemap-' . getmypid() . '.xml';

		try {
			wire('modules')->get('SeoMaestro')->getSitemapManager()->generate($file);
			$xml = simplexml_load_file($file);
		} finally {
			foreach ($hooks as $hookId) {
				wire()->removeHook($hookId);
			}
			@unlink($file);
		}

		$entries = [];
		foreach ($xml->url as $url) {
			$entries[] = [(string) $url->loc, (string) $url->lastmod, (string) $url->priority, (string) $url->changefreq];
		}

		self::assertContains($plainUrl, array_column($original, 0));
		self::assertSame($original, array_slice($entries, 0, count($original)));
		self::assertContains($this->service->getPerformanceUrl($p['period_cast_a']), array_column(array_slice($entries, count($original)), 0));
	}

	/**
	 * @return array<string, object>
	 */
	private function sitemapItemsByUrl(): array {
		$items = [];
		foreach ($this->service->getPerformanceSitemapItems() as $item) {
			$items[$item->loc] = $item;
		}

		return $items;
	}

	private function formatTitle(string $title): string {
		$format = (string) wire('fields')->get('seo')->get('meta_title_format');

		return $format === '' ? $title : str_replace('{meta_title}', $title, $format);
	}

	private function frontendBaseUrl(): string {
		$base = rtrim((string) wire('modules')->getConfig('SeoMaestro', 'baseUrl'), '/');
		self::assertNotSame('', $base, 'The test expects the SeoMaestro base URL to be configured.');

		return $base;
	}

	private function imageWidth(string $url): int {
		$path = (string) parse_url($url, PHP_URL_PATH);
		$root = wire('config')->urls->root;
		$file = wire('config')->paths->root . substr($path, strlen($root));
		self::assertFileExists($file);

		return (int) getimagesize($file)[0];
	}

	private function storedFieldData(int $fieldId): string {
		$query = wire('database')->prepare('SELECT data FROM fields WHERE id = :id');
		$query->execute([':id' => $fieldId]);

		return (string) $query->fetchColumn();
	}
}
