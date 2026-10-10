<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
use ProcessWire\Page;
use Tests\Support\FormFixtures;

use function ProcessWire\wire;

/**
 * Problems with the notification mail are written to the forms log, with
 * identifiers only and without any address.
 *
 * Mails are never sent: a hook replaces WireMail::send() and reports a failure.
 */
final class FormMailLogTest extends TestCase {
	private const RECIPIENT = 'form-log-test-recipient@example.invalid';
	private const SENDER = 'form-log-test-sender@example.invalid';

	private ?string $hookId = null;
	private ?Page $container = null;

	protected function setUp(): void {
		FormFixtures::delete();
		$this->hookId = wire()->addHookBefore('WireMail::send', function (HookEvent $event): void {
			$event->replace = true;
			$event->return = 0;
		});
	}

	protected function tearDown(): void {
		if ($this->hookId !== null) {
			wire()->removeHook($this->hookId);
		}
		FormFixtures::delete();
	}

	public function testFailedSendIsLoggedWithoutAddresses(): void {
		$this->container = FormFixtures::createContainer([['text', self::RECIPIENT]]);
		$request = $this->newRequest();

		$entries = $this->newLogEntries(fn () => $this->sendNotification($request));

		$log = implode("\n", $entries);
		self::assertStringContainsString('Mail not sent.', $log);
		self::assertStringContainsString('form page: ' . $this->container->id, $log);
		self::assertStringContainsString('template: ' . $this->container->template->name, $log);
		$this->assertNoPersonalData($log);
	}

	public function testMissingRecipientIsLoggedWithoutMailContent(): void {
		$this->container = FormFixtures::createContainer([['text', '']]);
		$request = $this->newRequest();

		$entries = $this->newLogEntries(fn () => $this->sendNotification($request));

		$log = implode("\n", $entries);
		self::assertStringContainsString('No recipient.', $log);
		self::assertStringContainsString('form page: ' . $this->container->id, $log);
		self::assertStringContainsString('template: ' . $this->container->template->name, $log);
		$this->assertNoPersonalData($log);
	}

	public function testMailerErrorsLoseTheirAddresses(): void {
		$this->container = FormFixtures::createContainer([['text', self::RECIPIENT]]);
		$form = $this->form();
		$mailer = new class {
			public function getErrors(): array {
				return ['550 5.1.1 <' . FormMailLogTest::mailAddress() . '> rejected', 'Connection closed'];
			}
		};

		$method = new \ReflectionMethod($form, 'getMailerErrors');
		$text = $method->invoke($form, $mailer);

		self::assertStringContainsString('rejected', $text);
		self::assertStringContainsString('Connection closed', $text);
		self::assertStringNotContainsString('@', $text);
		self::assertStringNotContainsString('example.invalid', $text);
	}

	public static function mailAddress(): string {
		return self::RECIPIENT;
	}

	private function assertNoPersonalData(string $log): void {
		foreach ([self::RECIPIENT, self::SENDER, 'example.invalid', 'Subject-Marker', 'Message-Marker'] as $needle) {
			self::assertStringNotContainsString($needle, $log);
		}
		self::assertDoesNotMatchRegularExpression('/\S+@\S+/', $log);
	}

	/**
	 * Log lines written while $action runs.
	 *
	 * @return string[]
	 */
	private function newLogEntries(callable $action): array {
		$file = wire('config')->paths->logs . 'forms.txt';
		$before = is_file($file) ? (string) file_get_contents($file) : '';
		$action();
		clearstatcache();
		$after = is_file($file) ? (string) file_get_contents($file) : '';
		self::assertStringStartsWith($before, $after);
		$added = trim(substr($after, strlen($before)));

		return $added === '' ? [] : explode("\n", $added);
	}

	private function newRequest(): Page {
		$request = new Page();
		$request->template = FormFixtures::FORM_TEMPLATE;
		$request->parent = $this->container;
		$request->name = 'test-fixture-form-request';
		$request->of(false);
		foreach ([
			'first_name' => 'Test',
			'last_name' => 'Person',
			'emailaddress' => self::SENDER,
			'short_text' => 'Subject-Marker',
			'freetext' => 'Message-Marker',
		] as $field => $value) {
			$request->set($field, $value);
		}
		$request->title = 'Subject-Marker';
		wire('pages')->save($request, ['quiet' => true]);

		return $request;
	}

	private function form(): object {
		$this->container->of(true);
		$forms = wire('twack')->getNewComponent('FormsComponent', ['directory' => '']);

		return $forms->addComponent('FormTemplate', [
			'containerPage' => $this->container,
			'page' => $this->container,
			'throwErrors' => true,
		]);
	}

	private function sendNotification(Page $request): void {
		$form = $this->form();
		$method = new \ReflectionMethod($form, 'sendNotification');
		$method->invoke($form, $request);
	}
}
