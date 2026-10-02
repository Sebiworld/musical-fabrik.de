<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\FormSubmissionGuard;
use ProcessWire\Page;
use Tests\Support\ApiTestCase;
use Tests\Support\FormFixtures;

/**
 * Form submissions over POST /api/tpage/<page>, as a guest. The form
 * container has no mail notification, so nothing is sent.
 *
 * The test server sees 127.0.0.1 as REMOTE_ADDR, which counts as a proxy on
 * the same host, so X-Forwarded-For selects the client per request.
 */
final class FormSubmissionTest extends ApiTestCase {
	private Page $container;
	private int $sequence = 0;

	protected function setUp(): void {
		parent::setUp();
		$fixtures = FormFixtures::create();
		$this->container = $fixtures['container'];
	}

	protected function tearDown(): void {
		FormFixtures::delete();
		parent::tearDown();
	}

	public function testANormalSubmissionIsStored(): void {
		$withoutHoneypot = $this->submit('203.0.113.21', [], false);
		$withEmptyHoneypot = $this->submit('203.0.113.21', ['website' => '']);

		foreach ([$withoutHoneypot, $withEmptyHoneypot] as $response) {
			self::assertSame(200, $response['status'], $response['raw']);
			self::assertTrue($response['json']['status']);
			self::assertIsString($response['json']['success']['finished']);
			self::assertIsInt($response['json']['request_id']);
		}
		self::assertSame(2, FormFixtures::submissionCount($this->container));

		$stored = \ProcessWire\wire('pages')->getFresh($withEmptyHoneypot['json']['request_id']);
		self::assertSame($this->container->id, $stored->parent_id);
		self::assertSame('Message 2', $stored->freetext);
	}

	public function testAFilledHoneypotAnswersLikeSuccessButStoresNothing(): void {
		$normal = $this->submit('203.0.113.31');
		$spam = $this->submit('203.0.113.32', ['website' => 'https://spam.example']);

		self::assertSame(200, $spam['status'], $spam['raw']);
		self::assertTrue($spam['json']['status']);
		self::assertSame($normal['json']['success'], $spam['json']['success']);
		self::assertArrayNotHasKey('request_id', $spam['json']);
		self::assertSame(1, FormFixtures::submissionCount($this->container), 'Only the normal submission is stored.');

		$lines = \ProcessWire\wire('log')->getLines(FormSubmissionGuard::HONEYPOT_LOG, ['limit' => 20]);
		$hits = array_values(array_filter($lines, fn ($line) => str_ends_with((string) $line, 'Form: ' . $this->container->id)));
		self::assertCount(1, $hits, 'The dropped submission is logged once.');
		self::assertStringNotContainsString('203.0.113', $hits[0]);
		self::assertStringNotContainsString('spam.example', $hits[0]);
	}

	public function testFailedValidationAndHoneypotHitsDoNotCount(): void {
		for ($i = 1; $i < FormSubmissionGuard::LIMIT; $i++) {
			self::assertSame(200, $this->submit('203.0.113.51')['status'], 'Submission ' . $i);
		}

		$invalid = $this->submit('203.0.113.51', ['first_name' => '']);
		self::assertSame(400, $invalid['status'], $invalid['raw']);
		$spam = $this->submit('203.0.113.51', ['website' => 'https://spam.example']);
		self::assertSame(200, $spam['status'], $spam['raw']);

		$last = $this->submit('203.0.113.51');
		self::assertSame(200, $last['status'], 'The last allowed submission must not be used up by the failed ones: ' . $last['raw']);
		self::assertSame(429, $this->submit('203.0.113.51')['status']);
		self::assertSame(FormSubmissionGuard::LIMIT, FormFixtures::submissionCount($this->container));
	}

	public function testParallelSubmissionsCannotPassTheLimit(): void {
		for ($i = 1; $i <= FormSubmissionGuard::LIMIT - 3; $i++) {
			self::assertSame(200, $this->submit('203.0.113.61')['status'], 'Submission ' . $i);
		}

		$requests = [];
		for ($i = 0; $i < 8; $i++) {
			$requests[] = $this->submitRequest('203.0.113.61');
		}
		$statuses = array_column($this->apiRequestsInParallel($requests), 'status');
		sort($statuses);

		self::assertSame([200, 200, 200, 429, 429, 429, 429, 429], $statuses);
		self::assertSame(FormSubmissionGuard::LIMIT, FormFixtures::submissionCount($this->container));
	}

	public function testErrorsUseTheApiErrorFormat(): void {
		$invalid = $this->submit('203.0.113.81', ['first_name' => '']);
		self::assertSame(400, $invalid['status'], $invalid['raw']);
		self::assertSame('form_validation_failed', $invalid['json']['errorcode']);
		self::assertIsString($invalid['json']['error']);
		self::assertNotSame('', $invalid['json']['error']);
		self::assertFalse($invalid['json']['fields']['first_name']['isSuccessful']);
		self::assertTrue($invalid['json']['fields']['last_name']['isSuccessful']);

		$rejected = $this->submit('203.0.113.81', ['information' => 'x']);
		self::assertSame(400, $rejected['status'], $rejected['raw']);
		self::assertSame('form_rejected', $rejected['json']['errorcode']);
		self::assertIsString($rejected['json']['error']);

		self::assertSame(0, FormFixtures::submissionCount($this->container));
	}

	public function testTheLimitAnswers429ForThatClientOnly(): void {
		for ($i = 1; $i <= FormSubmissionGuard::LIMIT; $i++) {
			$response = $this->submit('203.0.113.41');
			self::assertSame(200, $response['status'], 'Submission ' . $i . ': ' . $response['raw']);
		}
		self::assertSame(FormSubmissionGuard::LIMIT, FormFixtures::submissionCount($this->container));

		$blocked = $this->submit('203.0.113.41');
		self::assertSame(429, $blocked['status'], $blocked['raw']);
		self::assertSame(['errorcode', 'error'], array_keys($blocked['json']));
		self::assertSame('too_many_requests', $blocked['json']['errorcode']);
		self::assertNotSame('', $blocked['json']['error']);
		self::assertGreaterThan(0, (int) ($blocked['headers']['retry-after'] ?? 0));

		// A client-supplied address left of the proxy entry changes nothing.
		$spoofed = $this->submit('198.51.100.50, 203.0.113.41');
		self::assertSame(429, $spoofed['status'], $spoofed['raw']);

		self::assertSame(FormSubmissionGuard::LIMIT, FormFixtures::submissionCount($this->container), 'Nothing is stored over the limit.');

		$otherClient = $this->submit('203.0.113.42');
		self::assertSame(200, $otherClient['status'], $otherClient['raw']);
		self::assertSame(FormSubmissionGuard::LIMIT + 1, FormFixtures::submissionCount($this->container));
	}

	private function submit(string $forwardedFor, array $extra = [], bool $withHoneypot = true): array {
		return $this->apiRequest(...$this->submitRequest($forwardedFor, $extra, $withHoneypot));
	}

	/**
	 * @return array{0: string, 1: string, 2: array, 3: string}
	 */
	private function submitRequest(string $forwardedFor, array $extra = [], bool $withHoneypot = true): array {
		$this->sequence++;
		$fields = [
			'form-origin' => $this->container->id,
			'first_name' => 'Test',
			'last_name' => 'Person',
			'emailaddress' => 'form-test@example.invalid',
			'short_text' => 'Subject ' . $this->sequence,
			'freetext' => 'Message ' . $this->sequence,
		];
		if ($withHoneypot) {
			$fields['website'] = '';
		}

		return ['POST', 'tpage' . FormFixtures::PAGE_PATH, [
			'Content-Type: application/x-www-form-urlencoded',
			'X-Forwarded-For: ' . $forwardedFor,
		], http_build_query(array_merge($fields, $extra))];
	}
}
