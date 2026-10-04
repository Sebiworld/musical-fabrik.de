<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FormTemplate;
use ProcessWire\HookEvent;
use ProcessWire\Page;
use Tests\Support\FormFixtures;

use function ProcessWire\wire;

require_once wire('config')->paths->templates . 'components/forms_component/form_template/form_template.class.php';

/**
 * Thrown instead of sending the response, so the test can read it.
 */
final class CapturedFormResponse extends \Exception {
	public function __construct(public array $body, public int $status) {
		parent::__construct('Captured response');
	}
}

/**
 * A FormTemplate that hands its JSON response to the test instead of
 * sending it and exiting.
 */
final class CapturingFormTemplate extends FormTemplate {
	protected function sendJsonResponse(array $body, int $status) {
		throw new CapturedFormResponse($body, $status);
	}
}

/**
 * JSON error responses of a form submission, in-process and in API mode.
 */
final class FormErrorResponseTest extends TestCase {
	private const SECRET = 'SQLSTATE[42S22]: Column not found: 1054 Unknown column secret_column in /srv/site/assets/secret-path';

	private ?string $hookId = null;
	private ?Page $container = null;
	private array $location = [];

	protected function setUp(): void {
		FormFixtures::delete();
		$this->container = FormFixtures::createContainer();
		// As in a web request: formatted values (the form template as an object).
		$this->container->of(true);

		// Paths of the real component, read before any POST data is set:
		// with POST data, the real component would submit and exit.
		$forms = wire('twack')->getNewComponent('FormsComponent', ['directory' => '']);
		$template = $forms->addComponent('FormTemplate', [
			'containerPage' => $this->container,
			'page' => $this->container,
			'throwErrors' => true,
		]);
		$this->location = (fn () => $this->location)->call($template);
	}

	protected function tearDown(): void {
		if ($this->hookId !== null) {
			wire()->removeHook($this->hookId);
		}
		wire('twack')->disableAjaxResponse();
		foreach (array_keys(wire('input')->post->getArray()) as $key) {
			wire('input')->post->offsetUnset($key);
		}
		FormFixtures::delete();
	}

	public function testAnUnexpectedExceptionDoesNotReachTheClient(): void {
		$this->hookId = wire()->addHookBefore('Pages::save', function (HookEvent $event): void {
			$page = $event->arguments(0);
			if ($page instanceof Page && $page->template->name === FormFixtures::FORM_TEMPLATE) {
				throw new \RuntimeException(self::SECRET);
			}
		});

		foreach ([
			'form-origin' => $this->container->id,
			'first_name' => 'Test',
			'last_name' => 'Person',
			'emailaddress' => 'form-test@example.invalid',
			'short_text' => 'Subject',
			'freetext' => 'Message',
		] as $key => $value) {
			wire('input')->post->set($key, $value);
		}
		wire('twack')->enableAjaxResponse();

		$response = $this->capture(fn () => $this->newForm());

		self::assertSame(400, $response->status);
		self::assertSame('form_error', $response->body['errorcode']);
		self::assertIsString($response->body['error']);
		self::assertNotSame('', $response->body['error']);
		$json = json_encode($response->body);
		self::assertStringNotContainsString('SQLSTATE', $json);
		self::assertStringNotContainsString('secret', $json);

		$logged = implode("\n", wire('log')->getLines('forms', ['limit' => 10]));
		self::assertStringContainsString('Form ' . $this->container->id . ': RuntimeException: ' . self::SECRET, $logged);
		self::assertSame(0, FormFixtures::submissionCount($this->container));
	}

	public function testTheErrorcodeIsNotOverwrittenByMergedExceptionData(): void {
		$form = $this->newForm();
		wire('twack')->enableAjaxResponse();
		$respondError = new \ReflectionMethod($form, 'respondError');

		$response = $this->capture(fn () => $respondError->invoke($form, [
			'errorcode' => 'app_api_form_protection_exception',
			'error' => ['form_error' => 'Form already submitted.'],
			'try_again_in' => 120,
		], 400, 'form_already_submitted', 'Form already submitted.'));

		self::assertSame('form_already_submitted', $response->body['errorcode']);
		self::assertSame('Form already submitted.', $response->body['error']);
		self::assertSame(120, $response->body['try_again_in']);
		self::assertSame(['errorcode', 'error'], array_slice(array_keys($response->body), 0, 2));
	}

	private function newForm(): CapturingFormTemplate {
		return new CapturingFormTemplate([
			'location' => $this->location,
			'viewname' => 'FormTemplate',
			'containerPage' => $this->container,
			'page' => $this->container,
		]);
	}

	private function capture(callable $call): CapturedFormResponse {
		try {
			$call();
		} catch (CapturedFormResponse $response) {
			return $response;
		}
		self::fail('No JSON response was sent.');
	}
}
