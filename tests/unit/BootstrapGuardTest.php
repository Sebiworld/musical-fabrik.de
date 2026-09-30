<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The test bootstrap must stop before ProcessWire boots when MF_DB selects
 * anything other than the test database, and must not print the name of the
 * database it would have connected to.
 */
final class BootstrapGuardTest extends TestCase {
	public function testStopsBeforeBootingWhenAnotherDatabaseIsSelected(): void {
		$result = $this->runBootstrap('not-the-test-database');

		// The output is not passed to the assertions, so that a failure never
		// prints a database name.
		self::assertSame(1, $result['exitCode'], 'The bootstrap did not stop.');
		self::assertTrue(
			str_contains($result['output'], "MF_DB is set to a value other than 'test'"),
			'The bootstrap did not stop before booting ProcessWire.'
		);
		self::assertFalse(
			str_contains($result['output'], 'the connected database is not'),
			'The bootstrap booted ProcessWire before stopping.'
		);
	}

	public function testBootsWhenTheTestDatabaseIsSelected(): void {
		$result = $this->runBootstrap('test');

		self::assertSame(0, $result['exitCode'], 'The bootstrap refused the test database.');
	}

	/**
	 * @return array{exitCode: int, output: string}
	 */
	private function runBootstrap(string $mfDb): array {
		$environment = getenv();
		$environment['MF_DB'] = $mfDb;

		$process = proc_open(
			[PHP_BINARY, '-d', 'display_errors=stderr', dirname(__DIR__) . '/bootstrap.php'],
			[1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
			$pipes,
			sys_get_temp_dir(),
			$environment,
		);
		self::assertIsResource($process);
		$output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		return ['exitCode' => proc_close($process), 'output' => $output];
	}
}
