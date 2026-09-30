<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

use function ProcessWire\wire;

/**
 * Confirms the test bootstrap actually booted ProcessWire against the test
 * database, so later unit tests can rely on wire() being available.
 */
final class BootstrapSmokeTest extends TestCase {
	public function testProcessWireIsBootedAgainstTheTestDatabase(): void {
		self::assertTrue(function_exists('ProcessWire\\wire'), 'ProcessWire was not bootstrapped.');
		self::assertNotNull(wire('database'), 'wire(\'database\') is not available.');

		$dbName = wire('database')->query('SELECT DATABASE()')->fetchColumn();
		self::assertSame('mf_test', $dbName);
	}
}
