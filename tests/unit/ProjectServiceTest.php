<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * ProjectService against the seed project of the test database.
 */
final class ProjectServiceTest extends TestCase {
	private object $service;

	protected function setUp(): void {
		$this->service = wire('modules')->get('Twack')->getService('ProjectService');
	}

	public function testProjectPageResolvesToItself(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);

		self::assertSame($project->id, $this->service->getProjectPage($project)->id);
		self::assertTrue($this->service->isProjectPage($project));
	}

	public function testChildPageResolvesToItsProject(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);
		$role = Fixtures::page(Fixtures::ROLE_1_PATH);

		self::assertSame('project_role', $role->template->name);
		self::assertFalse($this->service->isProjectPage($role));
		self::assertSame($project->id, $this->service->getProjectPage($role)->id);
	}

	public function testPageOutsideAnyProjectHasNoProjectPage(): void {
		$content = Fixtures::page(Fixtures::CONTENT_PATH);

		self::assertFalse($this->service->isProjectPage($content));
		self::assertSame(0, $this->service->getProjectPage($content)->id);
	}

	public function testProjectAjaxDescribesTheProject(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);

		$ajax = $this->service->getProjectAjax($project);

		self::assertIsArray($ajax);
		self::assertSame($project->id, $ajax['id']);
		self::assertSame('test-fixture-project', $ajax['name']);
		self::assertSame('project', $ajax['template']['name']);
		self::assertNotEmpty($ajax['hash']);
	}

	public function testPortraitsContainerBelongsToTheProject(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);

		$container = $this->service->getPortraitsContainer($project);

		self::assertSame('portraits_container', $container->template->name);
		self::assertSame($project->id, $container->parent->id);
	}
}
