<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\Page;
use ProcessWire\ProcessPageListActions;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * "View" in the admin opens a page in the frontend: URL of a page in the
 * frontend, the pages that have one, and the view action of the page list.
 */
final class FrontendViewUrlTest extends TestCase {
	private object $service;

	protected function setUp(): void {
		$this->service = wire('modules')->get('Twack')->getService('SeoService');
	}

	private function base(): string {
		$base = rtrim((string) wire('modules')->getConfig('SeoMaestro', 'baseUrl'), '/');
		self::assertNotSame('', $base, 'The test expects the SeoMaestro base URL to be configured.');

		return $base;
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function fixturePaths(): array {
		return [
			'project' => [Fixtures::PROJECT_PATH],
			'nested page' => [Fixtures::ROLE_1_PATH],
		];
	}

	public function testHomePageIsTheFrontendRoot(): void {
		$home = wire('pages')->get('/');

		self::assertSame($this->base() . '/', $this->service->getFrontendUrl($home));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('fixturePaths')]
	public function testPageUrlIsBaseAndPathWithTrailingSlash(string $path): void {
		$page = Fixtures::page($path);

		self::assertSame($this->base() . $path, $this->service->getFrontendUrl($page));
	}

	public function testUrlIsTheCanonicalUrlOfAPageWithoutSeoOverride(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);

		self::assertSame($this->service->getSeoAjax($project)['canonical'], $this->service->getFrontendUrl($project));
	}

	public function testUrlIgnoresTheCanonicalPathMaintainedInTheSeoField(): void {
		$pages = Fixtures::createSeoTree();
		try {
			self::assertSame($this->base() . '/' . Fixtures::SEO_MAINTAINED_NAME . '/', $this->service->getFrontendUrl($pages['maintained']));
		} finally {
			Fixtures::deleteSeoTree();
		}
	}

	public function testAdminPagesAndTrashHaveNoFrontendUrl(): void {
		$pages = wire('pages');
		$adminRoot = $pages->get((int) wire('config')->adminRootPageID);
		$trash = $pages->get((int) wire('config')->trashPageID);

		self::assertTrue($this->service->isAdminPage($adminRoot));
		self::assertTrue($this->service->isAdminPage($pages->get('template=admin, parent=' . $adminRoot->id)));
		self::assertFalse($this->service->hasFrontendUrl($adminRoot));
		self::assertFalse($this->service->hasFrontendUrl($trash));
		self::assertFalse($this->service->hasFrontendUrl(new \ProcessWire\NullPage()));
		self::assertTrue($this->service->hasFrontendUrl($pages->get('/')));
		self::assertTrue($this->service->hasFrontendUrl(Fixtures::page(Fixtures::PROJECT_PATH)));
	}

	public function testPageListViewActionPointsToTheFrontend(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);
		$actions = $this->listActions($project);

		self::assertArrayHasKey('view', $actions);
		self::assertSame($this->base() . Fixtures::PROJECT_PATH, $actions['view']['url']);
	}

	public function testPageListViewActionOfTheHomePageIsTheFrontendRoot(): void {
		$actions = $this->listActions(wire('pages')->get('/'));

		self::assertSame($this->base() . '/', $actions['view']['url']);
	}

	public function testPageListKeepsTheViewActionOfAdminPages(): void {
		$adminRoot = wire('pages')->get((int) wire('config')->adminRootPageID);
		$actions = $this->listActions($adminRoot);

		if (isset($actions['view'])) {
			self::assertStringNotContainsString($this->base(), $actions['view']['url']);
		} else {
			self::assertTrue($adminRoot->viewable() === false);
		}
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	private function listActions(Page $page): array {
		require_once wire('config')->paths->get('ProcessPageList') . 'ProcessPageListActions.php';
		$actions = wire(new ProcessPageListActions());

		return $actions->getActions($page);
	}
}
