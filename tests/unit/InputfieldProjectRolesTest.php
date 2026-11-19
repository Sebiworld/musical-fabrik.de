<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FieldtypeProjectRoleProjectRole;
use ProcessWire\FieldtypeProjectRoleProjectRolesArray;
use ProcessWire\Page;
use ProcessWire\PageArray;

use function ProcessWire\wire;

/**
 * The role field (input in the admin, and the string form of a value) prints
 * the titles of roles and casts as text, not as markup. The pages are built
 * in memory and never saved.
 */
final class InputfieldProjectRolesTest extends TestCase {
	private const MARKUP = '<img src=x onerror=alert(1)> & "Q"';

	public function testTitlesOfRolesAndCastsAreEscaped(): void {
		$inputfield = wire('modules')->get('InputfieldProjectRoles');
		self::assertNotNull($inputfield, 'The module InputfieldProjectRoles is installed.');

		$role = new Page();
		$role->template = 'project_role';
		$role->title = 'Role ' . self::MARKUP;
		$cast = new Page();
		$cast->template = 'cast';
		$cast->title = 'Cast ' . self::MARKUP;

		$roles = new PageArray();
		$roles->add($role);
		$casts = new PageArray();
		$casts->add($cast);

		$inputfield->attr('name', 'project_roles');
		$inputfield->attr('value', new FieldtypeProjectRoleProjectRolesArray());
		$inputfield->setProjectRoles($roles);
		$inputfield->setCasts($casts);

		$html = $inputfield->render();

		self::assertStringNotContainsString('<img', $html);
		$escaped = htmlspecialchars(self::MARKUP, ENT_QUOTES, 'UTF-8');
		self::assertStringContainsString('<span>Role ' . $escaped . '</span>', $html);
		self::assertStringContainsString('>Cast ' . $escaped . '</th>', $html);
	}

	public function testStringFormOfAValueEscapesTheTitles(): void {
		self::assertNotNull(wire('modules')->get('InputfieldProjectRoles'), 'Loads the classes of the field.');

		$role = new Page();
		$role->template = 'project_role';
		$role->title = 'Role ' . self::MARKUP;
		// The value looks up a role page without id in the database.
		$role->id = PHP_INT_MAX;
		$cast = new Page();
		$cast->template = 'cast';
		$cast->title = 'Cast ' . self::MARKUP;
		$casts = new PageArray();
		$casts->add($cast);

		$value = new FieldtypeProjectRoleProjectRole($role);
		$value->casts = $casts;
		$html = (string) $value;

		self::assertStringNotContainsString('<img', $html);
		$escaped = htmlspecialchars(self::MARKUP, ENT_QUOTES, 'UTF-8');
		self::assertStringContainsString('<strong>Role ' . $escaped . '</strong>', $html);
		self::assertStringContainsString('<em>Cast ' . $escaped . '</em>', $html);
	}
}
