<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\ResponseBody;

final class ResponseBodyTest extends TestCase {
	public function testCleanJsonDecodesWithoutWarning(): void {
		$warnings = [];
		$this->captureWarnings($warnings);
		try {
			$result = ResponseBody::decode('{"a":1}', 'ctx');
		} finally {
			restore_error_handler();
		}

		self::assertSame(['a' => 1], $result);
		self::assertSame([], $warnings);
	}

	public function testOutputBeforeJsonIsToleratedButWarnsWithoutLeakingContent(): void {
		$prefix = "<b>Deprecated</b>: secret-value\n";
		$warnings = [];
		$this->captureWarnings($warnings);
		try {
			$result = ResponseBody::decode($prefix . '{"a":1}', 'SomeTest::testX (GET some/route)');
		} finally {
			restore_error_handler();
		}

		self::assertSame(['a' => 1], $result);
		self::assertCount(1, $warnings);
		self::assertStringContainsString('SomeTest::testX (GET some/route)', $warnings[0]);
		self::assertStringContainsString((string) strlen($prefix), $warnings[0]);
		self::assertStringNotContainsString('secret-value', $warnings[0]);
	}

	public function testBodyWithoutJsonIsNull(): void {
		self::assertNull(ResponseBody::decode('plain text', 'ctx'));
		self::assertNull(ResponseBody::decode('', 'ctx'));
	}

	/**
	 * @param list<string> $warnings
	 */
	private function captureWarnings(array &$warnings): void {
		set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
			$warnings[] = $message;

			return true;
		}, E_USER_WARNING);
	}
}
