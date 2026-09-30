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
}
