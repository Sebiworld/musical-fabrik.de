<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
use ProcessWire\Page;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * The sitemap file follows the pages: a page change marks it as outdated, and
 * writing it replaces every outdated entry, also when no page belongs into it
 * (servers with $config->noindex). It lists public pages only, also when a
 * superuser triggers it. Every test writes into its own temp file, never into
 * the sitemap file of the backend.
 */
final class SitemapServiceTest extends TestCase {
	/** @var array<string, Page> */
	private static array $pages = [];

	private mixed $noindex;
	private array $fieldDefaults = [];
	private ?string $hookId = null;
	private string $file;
	private ?string $previousPath = null;
	private ?string $leftover = null;
	/** @var string[] */
	private array $extraHooks = [];

	public static function setUpBeforeClass(): void {
		require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';
		self::$pages = Fixtures::createLinkPreviewTree();
	}

	public static function tearDownAfterClass(): void {
		Fixtures::deleteLinkPreviewTree();
		self::$pages = [];
	}

	protected function setUp(): void {
		$this->noindex = wire('config')->noindex;
		$field = wire('fields')->get('seo');
		$this->fieldDefaults = [$field->get('sitemap_include'), $field->get('robots_noIndex')];
		// A fixed name keeps the work files of the service (lock, marker) to
		// one set across test runs.
		$this->file = sys_get_temp_dir() . '/mf-test-sitemap-service.xml';
		$this->previousPath = $this->service()->getPath();
		$this->service()->setPath($this->file);
		@unlink($this->service()->getRetryMarkerPath());
	}

	protected function tearDown(): void {
		foreach ($this->extraHooks as $hookId) {
			wire()->removeHook($hookId);
		}
		$this->extraHooks = [];
		$tmp = $this->service()->getTempPath();
		if (is_file($tmp)) {
			chmod($tmp, 0644);
			unlink($tmp);
		}
		@unlink($this->service()->getRetryMarkerPath());
		if ($this->leftover !== null) {
			@unlink($this->leftover);
			$this->leftover = null;
		}
		$this->service()->setPath($this->previousPath);
		@unlink($this->file);
		if ($this->hookId !== null) {
			wire()->removeHook($this->hookId);
			$this->hookId = null;
		}
		$field = wire('fields')->get('seo');
		$field->set('sitemap_include', $this->fieldDefaults[0]);
		$field->set('robots_noIndex', $this->fieldDefaults[1]);
		wire('config')->noindex = $this->noindex;
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
	}

	public function testPageChangeMarksTheSitemapOutdated(): void {
		file_put_contents($this->file, 'outdated');
		self::assertFalse($this->service()->isOutdated(), 'A new file is expected to be current.');

		$page = $this->page('special');
		$page->of(false);
		wire('pages')->save($page, ['quiet' => true]);

		clearstatcache(true, $this->file);
		self::assertSame(0, filemtime($this->file));
		self::assertTrue($this->service()->isOutdated());
	}

	public function testChangesOutsideThePublicTreeDoNotCount(): void {
		$service = $this->service();

		self::assertTrue($service->isRelevantChange($this->page('special')));
		self::assertFalse($service->isRelevantChange(wire('users')->getGuestUser()), 'user');
		$formTemplates = [];
		foreach (wire('templates') as $template) {
			if (str_starts_with($template->name, 'form_')) {
				$formTemplates[] = $template->name;
			}
		}
		$formEntry = $formTemplates ? wire('pages')->get('template=' . implode('|', $formTemplates) . ', include=all') : null;
		if ($formEntry instanceof Page && $formEntry->id) {
			self::assertFalse($service->isRelevantChange($formEntry), 'form entry');
		}
		$formsContainer = wire('pages')->get('template=forms_container, include=all');
		if ($formsContainer->id) {
			self::assertFalse($service->isRelevantChange($formsContainer), 'forms container');
		}
	}

	public function testNoindexServerReplacesAnOutdatedSitemapWithAnEmptyOne(): void {
		wire('config')->noindex = true;
		file_put_contents($this->file, '<urlset><url><loc>https://example.org/outdated/</loc></url></urlset>');
		touch($this->file, 0);

		self::assertTrue($this->service()->write());

		$xml = (string) file_get_contents($this->file);
		self::assertStringStartsWith('<?xml', $xml);
		self::assertStringContainsString('<urlset', $xml);
		self::assertStringNotContainsString('<url>', $xml);
		self::assertFalse($this->service()->isOutdated());
	}

	public function testWrittenSitemapListsOnlyPublicPagesAlsoForASuperuser(): void {
		$this->includeAllPagesInTheSitemap();
		$superuser = wire('users')->get('roles=superuser, sort=id');
		self::assertTrue($superuser->id > 0, 'The test needs a superuser.');
		wire('users')->setCurrentUser($superuser);
		self::assertTrue($this->page('not_released')->viewable(), 'The superuser is expected to see the page that is not released.');

		self::assertTrue($this->service()->write());
		$xml = (string) file_get_contents($this->file);

		self::assertSame($superuser->id, wire('user')->id, 'The current user must be restored.');
		self::assertStringContainsString($this->path('special') . '</loc>', $xml, 'public page');
		self::assertStringContainsString($this->path('hidden_child') . '</loc>', $xml, 'page below a hidden parent');
		$locked = [
			'not released' => 'not_released',
			'locked by permissions' => 'permissions',
			'locked by password' => 'password',
			'below a password protected parent' => 'password_child',
			'unpublished' => 'unpublished_role',
			'below an unpublished parent' => 'role_child',
		];
		foreach ($locked as $case => $key) {
			self::assertStringNotContainsString($this->path($key) . '</loc>', $xml, $case);
		}
	}

	public function testTheCommandLineDoesNotWriteTheSitemap(): void {
		touch($this->file, 0);

		self::assertFalse($this->service()->refreshAtEndOfRequest());
		clearstatcache(true, $this->file);
		self::assertSame(0, filemtime($this->file));
	}

	public function testOnlyNoEntriesCountAsAnEmptySitemap(): void {
		$service = $this->service();

		self::assertTrue($service->isEmptyResult(false, 0), 'no entries');
		self::assertFalse($service->isEmptyResult(false, 3), 'entries, but writing failed');
		self::assertFalse($service->isEmptyResult(false, null), 'unknown number of entries');
		self::assertFalse($service->isEmptyResult(true, 3), 'written');
	}

	public function testOnlyAdminRequestsWriteWithoutFastCgi(): void {
		$service = $this->service();

		self::assertTrue($service->mayRefreshAtEndOfRequest(true, false), 'FastCGI, guest or API request');
		self::assertTrue($service->mayRefreshAtEndOfRequest(true, true), 'FastCGI, admin request');
		self::assertTrue($service->mayRefreshAtEndOfRequest(false, true), 'no FastCGI, admin request');
		self::assertFalse($service->mayRefreshAtEndOfRequest(false, false), 'no FastCGI, guest or API request');
	}

	public function testOutputAndWarningsOfTheGenerationStayOutOfTheResponse(): void {
		wire('config')->noindex = true;
		$this->extraHooks[] = wire()->addHookAfter('SeoMaestro::sitemapItems', function (HookEvent $event): void {
			echo 'output-of-the-generation';
			trigger_error('warning-of-the-generation', E_USER_WARNING);
		});

		ob_start();
		$written = $this->service()->write();
		$output = (string) ob_get_clean();

		self::assertTrue($written);
		self::assertSame('', $output);
		self::assertStringContainsString('warning-of-the-generation', $this->lastLogLine());
	}

	public function testAFailedAttemptKeepsTheFileAndPausesFurtherAttempts(): void {
		wire('config')->noindex = true;
		file_put_contents($this->file, 'existing sitemap');
		touch($this->file, 0);
		$attempts = 0;
		$this->extraHooks[] = wire()->addHookAfter('SeoMaestro::sitemapItems', function (HookEvent $event) use (&$attempts): void {
			$attempts++;
			throw new \RuntimeException('failure-of-the-generation');
		});

		self::assertFalse($this->service()->write());
		self::assertFalse($this->service()->write(), 'second attempt within the pause');

		self::assertSame(1, $attempts, 'The second attempt is expected to wait for the pause.');
		self::assertSame('existing sitemap', file_get_contents($this->file));
		self::assertTrue($this->service()->isRetryBlocked());
		self::assertStringContainsString('failure-of-the-generation', $this->lastLogLine());

		// After the pause the next attempt runs again.
		touch($this->service()->getRetryMarkerPath(), time() - \ProcessWire\SitemapService::RETRY_DELAY - 1);
		self::assertFalse($this->service()->isRetryBlocked());
		self::assertFalse($this->service()->write());
		self::assertSame(2, $attempts);
	}

	public function testTheRetryMarkerIsSetBeforeTheAttemptAndRemovedAfterSuccess(): void {
		wire('config')->noindex = true;
		$marker = $this->service()->getRetryMarkerPath();
		$markerDuringTheAttempt = null;
		$this->extraHooks[] = wire()->addHookAfter('SeoMaestro::sitemapItems', function (HookEvent $event) use ($marker, &$markerDuringTheAttempt): void {
			clearstatcache(true, $marker);
			$markerDuringTheAttempt = is_file($marker);
		});

		self::assertTrue($this->service()->write());

		// A fatal error or a timeout would end the process here, so the
		// marker must already exist to pause the next attempts.
		self::assertTrue($markerDuringTheAttempt);
		clearstatcache(true, $marker);
		self::assertFileDoesNotExist($marker);
	}

	public function testLeftoversOfAbortedAttemptsAreRemoved(): void {
		wire('config')->noindex = true;
		$leftover = str_replace('-' . getmypid() . '.xml', '-99999999.xml', $this->service()->getTempPath());
		file_put_contents($leftover, '<urlset>');
		$this->leftover = $leftover;

		self::assertTrue($this->service()->write());

		clearstatcache(true, $leftover);
		self::assertFileDoesNotExist($leftover);
	}

	public function testAFailedWriteKeepsTheExistingSitemap(): void {
		$this->includeAllPagesInTheSitemap();
		file_put_contents($this->file, 'existing sitemap');
		touch($this->file, 0);
		// A read-only temp file, created during the generation (after the
		// leftovers are removed), makes writing it fail.
		$tmp = $this->service()->getTempPath();
		$this->extraHooks[] = wire()->addHookAfter('SeoMaestro::sitemapItems', function (HookEvent $event) use ($tmp): void {
			touch($tmp);
			chmod($tmp, 0444);
		});

		self::assertFalse($this->service()->write());

		self::assertSame('existing sitemap', file_get_contents($this->file));
		self::assertTrue($this->service()->isRetryBlocked());
	}

	/**
	 * Every page into the sitemap unless it says otherwise, changed in memory
	 * only: the test database runs with $config->noindex and field defaults
	 * that leave all pages out.
	 */
	private function includeAllPagesInTheSitemap(): void {
		wire('config')->noindex = false;
		$field = wire('fields')->get('seo');
		$field->set('sitemap_include', 1);
		$field->set('robots_noIndex', 0);
		$this->hookId = wire()->addHookAfter('SeoMaestro::renderSeoDataValue', function (HookEvent $event) {
			if ($event->arguments(0) === 'sitemap' && $event->arguments(1) === 'include') {
				$event->return = (int) $event->arguments(2);
			}
		}, ['priority' => 1000]);
	}

	private function lastLogLine(): string {
		$lines = wire('log')->getLines('sitemap', ['limit' => 1]);

		return (string) (reset($lines) ?: '');
	}

	private function service(): object {
		return wire('modules')->get('Twack')->getService('SitemapService');
	}

	private function page(string $key): Page {
		return wire('pages')->get('id=' . self::$pages[$key]->id . ', include=all');
	}

	private function path(string $key): string {
		return $this->page($key)->path;
	}
}
