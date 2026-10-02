<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
use ProcessWire\Page;
use Tests\Support\FormFixtures;

use function ProcessWire\wire;
use function ProcessWire\wireMail;

/**
 * The notification mail of a form submission. Submitted values are plain
 * text and must not change the HTML of the mail.
 *
 * Mails are never sent: a hook replaces WireMail::send() for every mailer
 * (WireMailSmtp included) and only records the mail. The recipient uses the
 * reserved .invalid domain as a second safeguard.
 */
final class FormNotificationMailTest extends TestCase {
	private const RECIPIENT = 'form-notification-test@example.invalid';

	/** @var array<int, array{to: array, subject: string, body: string, bodyHTML: string}> */
	private static array $sentMails = [];
	private ?string $hookId = null;
	private ?Page $container = null;

	protected function setUp(): void {
		FormFixtures::delete();
		self::$sentMails = [];
		$this->hookId = wire()->addHookBefore('WireMail::send', function (HookEvent $event): void {
			$mail = $event->object;
			self::$sentMails[] = [
				'to' => $mail->get('to'),
				'subject' => (string) $mail->get('subject'),
				'body' => (string) $mail->get('body'),
				'bodyHTML' => (string) $mail->get('bodyHTML'),
			];
			$event->replace = true;
			$event->return = 1;
		});
	}

	protected function tearDown(): void {
		if ($this->hookId !== null) {
			wire()->removeHook($this->hookId);
		}
		FormFixtures::delete();
	}

	public function testSubmittedHtmlIsMaskedInTheNotificationMail(): void {
		self::assertTrue(wireMail()->hasHook('send()'), 'The send hook must be in place before anything is sent.');

		$this->container = FormFixtures::createContainer([['text', self::RECIPIENT]]);
		$request = $this->newRequest([
			'first_name' => '<i>Eve</i>',
			'last_name' => 'Doe',
			'emailaddress' => 'eve@example.invalid',
			'short_text' => 'Question',
			'freetext' => "Line one <b>bold</b>\n<script>alert(1)</script><a href=\"https://phish.example\">Click</a>",
		]);

		$this->sendNotification($request);

		self::assertCount(1, self::$sentMails, 'Exactly one notification is expected.');
		$mail = self::$sentMails[0];
		self::assertSame([self::RECIPIENT], array_values($mail['to']));
		$html = $mail['bodyHTML'];

		self::assertStringNotContainsString('<script>', $html);
		self::assertStringNotContainsString('<b>bold</b>', $html);
		self::assertStringNotContainsString('<a href="https://phish.example">', $html);
		self::assertStringNotContainsString('<i>Eve</i>', $html, 'The title placeholder must be masked as well.');

		// {{field_contents}} and {{freetext}} both carry the message.
		self::assertSame(2, substr_count($html, 'Line one &lt;b&gt;bold&lt;/b&gt;<br>'), $html);
		self::assertSame(2, substr_count($html, '&lt;script&gt;alert(1)&lt;/script&gt;&lt;a href=&quot;https://phish.example&quot;&gt;Click&lt;/a&gt;'), $html);
		self::assertStringContainsString('Title: ' . date('d.m.Y') . ': Question (by &lt;i&gt;Eve&lt;/i&gt; Doe)', $html);

		// The plain text part keeps the text as entered.
		self::assertStringContainsString('<b>bold</b>', $mail['body']);
	}

	public function testAConfirmationGoesToTheSingleAddressFromTheEmailField(): void {
		self::assertTrue(wireMail()->hasHook('send()'));
		$this->container = FormFixtures::createContainer([['variable', '{{emailaddress}}']]);
		$request = $this->newRequest($this->values(['emailaddress' => 'sender@example.invalid']));

		$this->sendNotification($request);

		self::assertCount(1, self::$sentMails);
		self::assertSame(['sender@example.invalid'], array_values(self::$sentMails[0]['to']));
	}

	public function testNoConfirmationForAVariableThatIsNotAnEmailField(): void {
		self::assertTrue(wireMail()->hasHook('send()'));
		$this->container = FormFixtures::createContainer([['variable', '{{short_text}}']]);
		$request = $this->newRequest($this->values(['short_text' => 'one@example.invalid,two@example.invalid']));

		$this->sendNotification($request);

		self::assertCount(0, self::$sentMails, 'A text field must not choose recipients.');
		self::assertTrue(\ProcessWire\wire('pages')->getFresh($request->id)->id > 0, 'The submission itself stays stored.');
	}

	public function testNoConfirmationForSeveralAddressesOrMixedVariables(): void {
		self::assertTrue(wireMail()->hasHook('send()'));
		$this->container = FormFixtures::createContainer([
			['variable', '{{emailaddress}}, other@example.invalid'],
			['variable', '{{emailaddress}}{{short_text}}'],
		]);
		$request = $this->newRequest($this->values(['emailaddress' => 'sender@example.invalid']));

		$this->sendNotification($request);

		self::assertCount(0, self::$sentMails);
	}

	private function values(array $overrides): array {
		return array_merge([
			'first_name' => 'Test',
			'last_name' => 'Person',
			'emailaddress' => 'sender@example.invalid',
			'short_text' => 'Question',
			'freetext' => 'Message',
		], $overrides);
	}

	private function newRequest(array $values): Page {
		$request = new Page();
		$request->template = FormFixtures::FORM_TEMPLATE;
		$request->parent = $this->container;
		$request->name = 'test-fixture-form-request';
		$request->of(false);
		foreach ($values as $field => $value) {
			$request->set($field, $value);
		}
		$request->title = date('d.m.Y') . ': ' . $values['short_text'] . ' (by ' . $values['first_name'] . ' ' . $values['last_name'] . ')';
		wire('pages')->save($request, ['quiet' => true]);

		return $request;
	}

	private function sendNotification(Page $request): void {
		// As in a web request: formatted values (the form template as an object).
		$this->container->of(true);
		$forms = wire('twack')->getNewComponent('FormsComponent', ['directory' => '']);
		$form = $forms->addComponent('FormTemplate', [
			'containerPage' => $this->container,
			'page' => $this->container,
			'throwErrors' => true,
		]);

		$method = new \ReflectionMethod($form, 'sendNotification');
		$method->invoke($form, $request);
	}
}
