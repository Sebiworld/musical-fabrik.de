<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ProcessWire\Page;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * LinkPreviewService: preview data of a frontend path, always as seen by a
 * guest, and the HTML document for link preview services.
 */
final class LinkPreviewServiceTest extends TestCase {
	/** @var array<string, Page> */
	private static array $pages = [];

	/** @var array<string, Page> */
	private static array $performancePages = [];

	private object $service;
	private object $seoService;

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
		$this->service = wire('modules')->get('Twack')->getService('LinkPreviewService');
		$this->seoService = wire('modules')->get('Twack')->getService('SeoService');
	}

	protected function tearDown(): void {
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
		wire('twack')->disableAjaxResponse();
	}

	public function testContentPageProjectAndPerformanceGetTheirSeoValues(): void {
		$period = self::$performancePages['period_cast_a'];
		$cases = [
			Fixtures::CONTENT_PATH => Fixtures::page(Fixtures::CONTENT_PATH),
			Fixtures::PROJECT_PATH => Fixtures::page(Fixtures::PROJECT_PATH),
			self::$performancePages['project']->path . 'vorstellungen/' . $period->id . '/' => $period,
		];

		foreach ($cases as $path => $page) {
			$preview = $this->service->getPreview($path);
			$seo = $this->seoService->getSeoAjax($page);

			self::assertIsArray($preview, $path);
			self::assertSame($seo['title'], $preview['title'], $path);
			self::assertSame($seo['description'], $preview['description'], $path);
			self::assertSame($seo['canonical'], $preview['canonical'], $path);
			self::assertSame($seo['image'], $preview['image'], $path);
		}
	}

	public function testHomePageIsFound(): void {
		$preview = $this->service->getPreview('/');

		self::assertIsArray($preview);
		self::assertSame($this->seoService->getSeoAjax(wire('pages')->get('/'))['canonical'], $preview['canonical']);
	}

	public function testImageSizeIsTheSizeOfThePreviewImage(): void {
		$preview = $this->service->getDefaultPreview();
		if ($preview['image'] === null) {
			self::markTestSkipped('The configuration page of the test database has no image.');
		}

		$file = wire('config')->paths->root . ltrim((string) parse_url($preview['image'], PHP_URL_PATH), '/');
		$size = getimagesize($file);
		self::assertIsArray($size, $file);
		self::assertSame([$size[0], $size[1]], [$preview['image_width'], $preview['image_height']]);
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function hiddenPages(): array {
		return [
			'unpublished' => ['unpublished'],
			'release time in the future' => ['not_released'],
			'locked by permissions' => ['permissions'],
			'locked by password' => ['password'],
			'child of a page locked by password' => ['password_child'],
			'role below an unpublished role' => ['role_child'],
			'portrait' => ['portrait'],
			'performance not for guests' => ['period_not_for_guests'],
			'unpublished performance' => ['period_unpublished'],
			'performance not released' => ['period_not_released'],
			'admin' => ['admin'],
			'unknown' => ['unknown'],
		];
	}

	#[DataProvider('hiddenPages')]
	public function testPathsAGuestMayNotSeeAreNotFound(string $key): void {
		self::assertNull($this->service->getPreview($this->pathOf($key)));
	}

	public function testPagesOfTheTestTreeExistForTheNotFoundCases(): void {
		foreach (['not_released', 'permissions', 'password', 'password_child', 'role_child'] as $key) {
			$page = wire('pages')->get('id=' . self::$pages[$key]->id . ', include=all');
			self::assertSame($this->pathOf($key), $page->path, $key);
			self::assertFalse($page->isUnpublished(), $key);
		}
	}

	public function testRoleBelowAnUnpublishedRoleIsViewableButNotPreviewed(): void {
		$child = wire('pages')->get('id=' . self::$pages['role_child']->id . ', include=all');
		self::assertTrue($child->parent->isUnpublished(), 'The parent role is expected to be unpublished.');
		self::assertTrue($child->viewable(), 'The child role is expected to be viewable for guests.');

		self::assertNull($this->service->getPreview($child->path));
	}

	public function testChildOfAHiddenPageGetsItsOwnValues(): void {
		$page = wire('pages')->get('id=' . self::$pages['hidden_child']->id . ', include=all');

		$preview = $this->service->getPreview($page->path);

		self::assertIsArray($preview);
		self::assertSame($this->seoService->getSeoAjax($page)['canonical'], $preview['canonical']);
	}

	public function testHiddenPageGetsItsOwnValues(): void {
		$page = wire('pages')->get('id=' . self::$pages['hidden']->id . ', include=all');
		self::assertTrue($page->isHidden(), 'The test page is expected to be hidden.');

		$preview = $this->service->getPreview($page->path);

		self::assertIsArray($preview);
		self::assertSame($this->seoService->getSeoAjax($page)['title'], $preview['title']);
		self::assertSame($this->seoService->getSeoAjax($page)['canonical'], $preview['canonical']);
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function pathVariants(): array {
		return [
			'without trailing slash' => ['/test-fixture-content'],
			'with query' => ['/test-fixture-content/?utm_source=x&b=1'],
			'with fragment' => ['/test-fixture-content#abschnitt'],
			'percent encoded' => ['/test%2Dfixture%2Dcontent/'],
			'encoded slashes' => ['%2Ftest-fixture-content%2F'],
			'upper case' => ['/Test-Fixture-Content/'],
			'double slashes' => ['//test-fixture-content//'],
		];
	}

	#[DataProvider('pathVariants')]
	public function testPathVariantsFindThePage(string $path): void {
		$preview = $this->service->getPreview($path);

		self::assertIsArray($preview, $path);
		self::assertSame($this->seoService->getSeoAjax(Fixtures::page(Fixtures::CONTENT_PATH))['canonical'], $preview['canonical']);
	}

	public function testPerformancePathWithoutTrailingSlashIsFound(): void {
		$period = self::$performancePages['period_cast_a'];

		$preview = $this->service->getPreview(self::$performancePages['project']->path . 'vorstellungen/' . $period->id);

		self::assertIsArray($preview);
		self::assertSame($this->seoService->getPerformanceUrl($period), $preview['canonical']);
	}

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public static function invalidPaths(): array {
		return [
			'parent segment' => ['/test-fixture-content/../'],
			'parent segment encoded' => ['/test-fixture-content/%2E%2E/'],
			'current segment' => ['/./test-fixture-content/'],
			'script tag' => ['/<script>alert(1)</script>/'],
			'script tag in a page name' => ['/test-fixture-<script>content/'],
			'character the sanitizer removes' => ['/test-fixture-content!/'],
			'leading dot the sanitizer removes' => ['/.test-fixture-content/'],
			'umlaut' => ['/test-fixture-cöntent/'],
			'umlaut encoded' => ['/test-fixture-c%C3%B6ntent/'],
			'invalid UTF-8' => ['/test-fixture-content%FF/'],
			'control character' => ["/test-fixture-content\x01/"],
			'empty' => [''],
			'array' => [['/test-fixture-content/']],
			'null' => [null],
			'too long' => ['/' . str_repeat('a', 2000) . '/'],
			'21 segments' => [str_repeat('/test-fixture-content', 21) . '/'],
			'62 segments, more tables than MySQL joins' => [str_repeat('/a', 62) . '/'],
		];
	}

	#[DataProvider('invalidPaths')]
	public function testInvalidPathsAreNotFound(mixed $path): void {
		self::assertNull($this->service->getPreview($path));
	}

	public function testALoggedInSuperuserGetsTheGuestView(): void {
		$superuser = wire('users')->get('roles=superuser, sort=id');
		self::assertTrue($superuser->id > 0, 'The test needs a superuser.');
		wire('users')->setCurrentUser($superuser);
		self::assertTrue(Fixtures::page(Fixtures::UNPUBLISHED_PATH)->viewable(), 'The superuser is expected to see the unpublished page.');
		self::assertTrue(wire('pages')->get('id=' . self::$pages['not_released']->id . ', include=all')->viewable(), 'The superuser is expected to see the page with a future release time.');

		$unpublished = $this->service->getPreview(Fixtures::UNPUBLISHED_PATH);
		$locked = $this->service->getPreview($this->pathOf('not_released'));
		$performance = $this->service->getPreview($this->pathOf('period_not_for_guests'));
		$content = $this->service->getPreview(Fixtures::CONTENT_PATH);

		self::assertSame($superuser->id, wire('user')->id, 'The current user must be restored.');
		self::assertNull($unpublished);
		self::assertNull($locked);
		self::assertNull($performance);
		self::assertIsArray($content);
	}

	public function testDefaultPreviewHasOnlyTheValuesOfTheSite(): void {
		$preview = $this->service->getDefaultPreview();
		$siteName = trim((string) wire('pages')->get('template.name=configuration, include=all')->seo_title);

		self::assertSame($siteName, $preview['title']);
		self::assertSame($this->seoService->getSeoAjax(wire('pages')->get('/'))['canonical'], $preview['canonical']);
	}

	public function testHtmlEscapesQuotesAmpersandsAndLessThanSigns(): void {
		$preview = $this->service->getPreview($this->pathOf('special'));
		self::assertIsArray($preview);
		self::assertStringStartsWith(Fixtures::LINK_PREVIEW_SPECIAL_TITLE, $preview['title']);
		self::assertSame(Fixtures::LINK_PREVIEW_SPECIAL_DESCRIPTION, $preview['description']);

		$html = $this->service->renderHtml($preview);
		$title = htmlspecialchars($preview['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

		self::assertStringContainsString('<title>' . $title . '</title>', $html);
		self::assertStringContainsString('<meta property="og:title" content="' . $title . '">', $html);
		self::assertStringContainsString('<meta name="description" content="Text &quot;x&quot; &amp; y &lt; z">', $html);
		self::assertStringContainsString('<h1>' . $title . '</h1>', $html);
		self::assertStringNotContainsString(Fixtures::LINK_PREVIEW_SPECIAL_TITLE, $html);
		self::assertStringNotContainsString(Fixtures::LINK_PREVIEW_SPECIAL_DESCRIPTION, $html);
	}

	public function testHtmlEscapesUrlsAndHasNoScript(): void {
		$html = $this->service->renderHtml([
			'title' => '<script>alert(1)</script>',
			'description' => '"><script>alert(2)</script>',
			'canonical' => 'https://example.org/"><script>alert(3)</script>',
			'image' => 'https://example.org/a.jpg?x="><script>',
			'image_width' => null,
			'image_height' => null,
		]);

		self::assertStringNotContainsStringIgnoringCase('<script', $html);
		self::assertStringContainsString('<link rel="canonical" href="https://example.org/&quot;&gt;&lt;script&gt;alert(3)&lt;/script&gt;">', $html);
		self::assertStringNotContainsString('og:image:width', $html);
	}

	public function testHtmlHasTheContractTags(): void {
		$preview = $this->service->getPreview(Fixtures::CONTENT_PATH);
		$preview['image'] = 'https://example.org/a.jpg';
		$preview['image_width'] = 1200;
		$preview['image_height'] = 630;

		$html = $this->service->renderHtml($preview);

		self::assertMatchesRegularExpression('#^<!doctype html>\s*<html lang="de">#', $html);
		foreach ([
			'<link rel="canonical" href="' . $preview['canonical'] . '">',
			'<meta property="og:url" content="' . $preview['canonical'] . '">',
			'<meta property="og:type" content="website">',
			'<meta property="og:locale" content="de_DE">',
			'<meta property="og:image" content="https://example.org/a.jpg">',
			'<meta property="og:image:width" content="1200">',
			'<meta property="og:image:height" content="630">',
			'<meta name="twitter:card" content="summary_large_image">',
			'<meta name="twitter:image" content="https://example.org/a.jpg">',
			'<a href="' . $preview['canonical'] . '">',
		] as $tag) {
			self::assertStringContainsString($tag, $html);
		}
		foreach (['og:site_name', 'og:title', 'og:description', 'twitter:title', 'twitter:description'] as $name) {
			self::assertMatchesRegularExpression('#<meta (property|name)="' . preg_quote($name, '#') . '" content="[^"]+">#', $html, $name);
		}
		self::assertStringNotContainsStringIgnoringCase('<script', $html);
	}

	private function pathOf(string $key): string {
		return match ($key) {
			'unpublished' => Fixtures::UNPUBLISHED_PATH,
			'portrait' => Fixtures::PORTRAIT_1_PATH,
			'admin' => wire('pages')->get(wire('config')->adminRootPageID)->path,
			'unknown' => '/test-fixture-does-not-exist/',
			'period_not_for_guests', 'period_unpublished', 'period_not_released' => self::$performancePages['project']->path . 'vorstellungen/' . self::$performancePages[$key]->id . '/',
			default => self::$pages[$key]->path,
		};
	}
}
