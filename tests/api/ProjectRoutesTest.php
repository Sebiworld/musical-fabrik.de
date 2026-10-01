<?php

declare(strict_types=1);
namespace Tests\Api;

use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

/**
 * Routes `projects`, `project-roles` and `project-portraits` for the seed
 * project, requested without a session.
 */
final class ProjectRoutesTest extends ApiTestCase {
	public function testProjectListContainsTheSeedProject(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);

		$response = $this->apiRequest('GET', 'projects');

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertArrayHasKey('hash', $response['json']);
		self::assertArrayHasKey((string) $project->id, $response['json']['projects']);
		self::assertSame('test-fixture-project', $response['json']['projects'][$project->id]['name']);
	}

	public function testProjectListLeavesOutUnpublishedPages(): void {
		$unpublished = Fixtures::page(Fixtures::UNPUBLISHED_PATH);

		$response = $this->apiRequest('GET', 'projects');

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertArrayNotHasKey((string) $unpublished->id, $response['json']['projects']);
	}

	public function testProjectDetailHasTheExpectedShape(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);

		$response = $this->apiRequest('GET', 'projects/' . $project->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$json = $response['json'];
		self::assertSame($project->id, $json['id']);
		self::assertSame('Test Fixture Project', $json['title']);
		self::assertSame('project', $json['template']['name']);
		self::assertNotEmpty($json['hash']);
		self::assertArrayHasKey('general', $json);
		self::assertArrayHasKey('images', $json);
		self::assertArrayHasKey('events', $json);
	}

	public function testProjectDetailOfUnpublishedPageIsForbidden(): void {
		$unpublished = Fixtures::page(Fixtures::UNPUBLISHED_PATH);

		$response = $this->apiRequest('GET', 'projects/' . $unpublished->id);

		self::assertSame(403, $response['status'], $response['raw']);
		self::assertSame('forbidden_exception', $response['json']['errorcode']);
	}

	public function testProjectRolesListRolesAndTheirPortraits(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);
		$role1 = Fixtures::page(Fixtures::ROLE_1_PATH);
		$role2 = Fixtures::page(Fixtures::ROLE_2_PATH);
		$role3 = Fixtures::page(Fixtures::ROLE_3_PATH);
		$portrait1 = Fixtures::page(Fixtures::PORTRAIT_1_PATH);
		$portrait2 = Fixtures::page(Fixtures::PORTRAIT_2_PATH);

		$response = $this->apiRequest('GET', 'project-roles/' . $project->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$json = $response['json'];
		self::assertSame($this->sorted([$role1->id, $role2->id, $role3->id]), $this->sortedKeys($json['roles']));
		self::assertSame('project_role', $json['roles'][$role1->id]['template']['name']);
		self::assertSame('Test Fixture Role 1', $json['roles'][$role1->id]['title']);
		self::assertSame(
			[$portrait1->id, $portrait2->id],
			$this->sorted($json['roles'][$role1->id]['participants'][0]['portrait_ids'])
		);
		self::assertSame([$portrait2->id], $json['roles'][$role2->id]['participants'][0]['portrait_ids']);
		self::assertSame([$portrait1->id, $portrait2->id], $this->sortedKeys($json['portraits']));
		self::assertArrayHasKey('seasons', $json);
		self::assertArrayHasKey('casts', $json);
	}

	public function testProjectRolesOfUnpublishedPageAreForbidden(): void {
		$unpublished = Fixtures::page(Fixtures::UNPUBLISHED_PATH);

		$response = $this->apiRequest('GET', 'project-roles/' . $unpublished->id);

		self::assertSame(403, $response['status'], $response['raw']);
		self::assertSame('forbidden_exception', $response['json']['errorcode']);
	}

	public function testProjectRolesOfUnknownIdAreNotFound(): void {
		$response = $this->apiRequest('GET', 'project-roles/2147483000');

		self::assertSame(404, $response['status'], $response['raw']);
		self::assertSame('not_found_exception', $response['json']['errorcode']);
	}

	public function testProjectPortraitsReturnTheRequestedPortraits(): void {
		$portrait1 = Fixtures::page(Fixtures::PORTRAIT_1_PATH);
		$portrait2 = Fixtures::page(Fixtures::PORTRAIT_2_PATH);

		$response = $this->apiRequest('GET', 'project-portraits?ids=' . $portrait1->id . ',' . $portrait2->id);

		self::assertSame(200, $response['status'], $response['raw']);
		$json = $response['json'];
		self::assertSame([$portrait1->id, $portrait2->id], $this->sorted($json['ids']));
		self::assertSame([$portrait1->id, $portrait2->id], $this->sortedKeys($json['portraits']));
		self::assertSame('Test Portrait 1', $json['portraits'][$portrait1->id]['title']);
		self::assertSame('Test', $json['portraits'][$portrait1->id]['first_name']);
		self::assertNotEmpty($json['hash']);
	}

	public function testProjectPortraitsWithoutIdsAreEmpty(): void {
		$response = $this->apiRequest('GET', 'project-portraits');

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertSame([], $response['json']['ids']);
		self::assertSame([], $response['json']['portraits']);
	}

	/**
	 * @return list<int>
	 */
	private function sortedKeys(array $map): array {
		return $this->sorted(array_map('intval', array_keys($map)));
	}

	/**
	 * @return list<int>
	 */
	private function sorted(array $ids): array {
		$ids = array_map('intval', $ids);
		sort($ids);

		return $ids;
	}
}
