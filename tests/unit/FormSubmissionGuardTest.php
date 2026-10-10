<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FormSubmissionGuard;
use ProcessWire\WireInputData;
use Tests\Support\FormFixtures;

/**
 * Honeypot detection, client address and submission limit of form submissions.
 * The limit is kept in WireCache; each test uses form ids no page has.
 */
final class FormSubmissionGuardTest extends TestCase {
	private const FORM = 999999001;
	private const OTHER_FORM = 999999002;

	public static function setUpBeforeClass(): void {
		FormFixtures::loadGuard();
	}

	protected function setUp(): void {
		FormSubmissionGuard::clearForm(self::FORM);
		FormSubmissionGuard::clearForm(self::OTHER_FORM);
	}

	protected function tearDown(): void {
		FormSubmissionGuard::clearForm(self::FORM);
		FormSubmissionGuard::clearForm(self::OTHER_FORM);
	}

	private static function post(array $values): WireInputData {
		// WireInputData takes its input by reference.
		return new WireInputData($values);
	}

	public function testTheHoneypotIsEmptyWhenMissingOrBlank(): void {
		self::assertFalse(FormSubmissionGuard::isHoneypotFilled(self::post([])));
		self::assertFalse(FormSubmissionGuard::isHoneypotFilled(self::post(['website' => ''])));
		self::assertFalse(FormSubmissionGuard::isHoneypotFilled(self::post(['website' => '  '])));
		self::assertFalse(FormSubmissionGuard::isHoneypotFilled(self::post(['first_name' => 'Anna', 'freetext' => 'Hello'])));
	}

	public function testTheHoneypotIsFilledByAnyValue(): void {
		self::assertTrue(FormSubmissionGuard::isHoneypotFilled(self::post(['website' => 'https://spam.example'])));
		self::assertTrue(FormSubmissionGuard::isHoneypotFilled(self::post(['website' => '0'])));
		self::assertTrue(FormSubmissionGuard::isHoneypotFilled(self::post(['website' => ['x']])));
	}

	public function testThePublicRemoteAddressIsUsedAndForwardedForIsIgnored(): void {
		self::assertSame('8.8.8.8', FormSubmissionGuard::clientIp(['REMOTE_ADDR' => '8.8.8.8']));
		self::assertSame('8.8.8.8', FormSubmissionGuard::clientIp([
			'REMOTE_ADDR' => '8.8.8.8',
			'HTTP_X_FORWARDED_FOR' => '1.1.1.1',
		]));
	}

	public function testBehindAPrivateProxyTheRightmostPublicForwardedAddressIsUsed(): void {
		self::assertSame('203.0.113.7', FormSubmissionGuard::clientIp([
			'REMOTE_ADDR' => '127.0.0.1',
			'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
		]));
		// The left entry is client-supplied; the proxy appended the real client.
		self::assertSame('203.0.113.7', FormSubmissionGuard::clientIp([
			'REMOTE_ADDR' => '10.0.0.5',
			'HTTP_X_FORWARDED_FOR' => '198.51.100.99, 203.0.113.7',
		]));
		// Internal hops on the right are skipped.
		self::assertSame('203.0.113.7', FormSubmissionGuard::clientIp([
			'REMOTE_ADDR' => '10.0.0.5',
			'HTTP_X_FORWARDED_FOR' => '203.0.113.7, 10.0.0.9',
		]));
	}

	public function testBrokenOrPrivateForwardedForFallsBackToTheRemoteAddress(): void {
		self::assertSame('127.0.0.1', FormSubmissionGuard::clientIp(['REMOTE_ADDR' => '127.0.0.1']));
		self::assertSame('127.0.0.1', FormSubmissionGuard::clientIp([
			'REMOTE_ADDR' => '127.0.0.1',
			'HTTP_X_FORWARDED_FOR' => '203.0.113.7, not-an-ip',
		]));
		self::assertSame('10.0.0.5', FormSubmissionGuard::clientIp([
			'REMOTE_ADDR' => '10.0.0.5',
			'HTTP_X_FORWARDED_FOR' => '192.168.1.20',
		]));
	}

	public function testTheLimitIsReachedAfterLimitSubmissions(): void {
		$guard = new FormSubmissionGuard(self::FORM, '203.0.113.7', 3, 600);
		$now = 1_900_000_000;

		$guard->recordSubmission($now);
		$guard->recordSubmission($now + 1);
		self::assertFalse($guard->isLimitReached($now + 2), 'Two of three submissions must not reach the limit.');

		$guard->recordSubmission($now + 2);
		self::assertTrue($guard->isLimitReached($now + 3));
		self::assertSame(597, $guard->retryAfter($now + 3));
	}

	public function testTheLimitCountsPerClientAndForm(): void {
		$now = 1_900_000_000;
		$guard = new FormSubmissionGuard(self::FORM, '203.0.113.7', 1, 600);
		$guard->recordSubmission($now);
		self::assertTrue($guard->isLimitReached($now));

		self::assertFalse((new FormSubmissionGuard(self::FORM, '203.0.113.8', 1, 600))->isLimitReached($now), 'Another client');
		self::assertFalse((new FormSubmissionGuard(self::OTHER_FORM, '203.0.113.7', 1, 600))->isLimitReached($now), 'Another form');
	}

	public function testIpv6ClientsOfOneNetworkShareTheLimit(): void {
		$now = 1_900_000_000;
		(new FormSubmissionGuard(self::FORM, '2a01:4f8:1:2::1', 1, 600))->recordSubmission($now);

		self::assertTrue((new FormSubmissionGuard(self::FORM, '2a01:4f8:1:2::ffff', 1, 600))->isLimitReached($now), 'Same /64');
		self::assertFalse((new FormSubmissionGuard(self::FORM, '2a01:4f8:1:3::1', 1, 600))->isLimitReached($now), 'Other /64');
	}

	public function testSubmissionsLeaveTheWindow(): void {
		$guard = new FormSubmissionGuard(self::FORM, '203.0.113.7', 2, 600);
		$now = 1_900_000_000;
		$guard->recordSubmission($now);
		$guard->recordSubmission($now + 100);
		self::assertTrue($guard->isLimitReached($now + 599));

		self::assertFalse($guard->isLimitReached($now + 600), 'The first submission has left the window.');
		$guard->recordSubmission($now + 600);
		self::assertTrue($guard->isLimitReached($now + 601));
	}

	public function testCountersWithAnotherPrefixAreSeparate(): void {
		$now = 1_900_000_000;
		$prefix = 'test-guard-prefix-';
		$other = new FormSubmissionGuard(self::FORM, '203.0.113.7', 1, 600, $prefix);
		try {
			$other->recordSubmission($now);
			self::assertTrue($other->isLimitReached($now));
			self::assertFalse((new FormSubmissionGuard(self::FORM, '203.0.113.7', 1, 600))->isLimitReached($now), 'The form counter is not affected.');

			FormSubmissionGuard::clearForm(self::FORM);
			self::assertTrue($other->isLimitReached($now), 'Clearing the form counters keeps the other counters.');
		} finally {
			\ProcessWire\wire('cache')->delete($prefix . self::FORM . '-*');
		}
	}

	public function testTheCacheNameDoesNotContainTheAddress(): void {
		$now = time();
		(new FormSubmissionGuard(self::FORM, '203.0.113.7'))->recordSubmission($now);

		$prefix = FormSubmissionGuard::CACHE_PREFIX . self::FORM . '-';
		$query = \ProcessWire\wire('database')->prepare('SELECT name FROM caches WHERE name LIKE :prefix');
		$query->execute(['prefix' => $prefix . '%']);
		$names = $query->fetchAll(\PDO::FETCH_COLUMN);

		self::assertCount(1, $names);
		self::assertMatchesRegularExpression('/^' . preg_quote($prefix, '/') . '[0-9a-f]{32}$/', $names[0]);
	}
}
