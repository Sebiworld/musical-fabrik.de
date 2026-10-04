<?php

declare(strict_types=1);
namespace Tests\Support;

use ProcessWire\FormSubmissionGuard;
use ProcessWire\Page;

use function ProcessWire\wire;

/**
 * Creates a form for submission tests: a form container that stores
 * `form_contact` requests and a published page that shows the form as a
 * content block. Remove everything again with delete().
 *
 * The container only has a mail notification when a recipient is given, so
 * submissions over HTTP never send mail.
 */
final class FormFixtures {
	public const CONTAINER_NAME = 'test-fixture-form-container';
	public const PAGE_NAME = 'test-fixture-form-page';
	public const PAGE_PATH = '/' . self::PAGE_NAME . '/';
	public const FORM_TEMPLATE = 'form_contact';

	/**
	 * @param array<int, array{0: string, 1: string}> $recipients notification
	 *        recipients as [type, value]: ['text', '<address>'] or
	 *        ['variable', '{{field}}']. Without recipients the container has
	 *        no notification at all.
	 */
	public static function createContainer(array $recipients = []): Page {
		$parent = wire('pages')->get('template=forms_container, include=all')->parent;
		if (!$parent->id) {
			throw new \RuntimeException('No parent for form containers found. Is the test database prepared?');
		}

		$container = new Page();
		$container->template = 'forms_container';
		$container->parent = $parent;
		$container->name = self::CONTAINER_NAME;
		$container->title = 'Test Fixture Form Container';
		$container->of(false);
		$container->form_template = [wire('templates')->get(self::FORM_TEMPLATE)->id];
		wire('pages')->save($container, ['quiet' => true]);

		if (!empty($recipients)) {
			$notification = $container->email_notification->getNew();
			$notification->of(false);
			$notification->short_text = 'Test notification';
			$notification->freetext = '<p>{{field_contents}}</p><p>Message: {{freetext}}</p><p>Title: {{title}}</p>';
			$notification->save();

			foreach ($recipients as [$type, $value]) {
				$recipient = $notification->email_recipient->getNew();
				$recipient->of(false);
				$recipient->setMatrixType($type);
				if ($type === 'variable') {
					$recipient->short_text = $value;
				} else {
					$recipient->email = $value;
				}
				$recipient->save();
				$notification->email_recipient->add($recipient);
			}
			$notification->save();

			$container->email_notification->add($notification);
			wire('pages')->save($container, ['quiet' => true]);
		}

		return wire('pages')->getFresh($container->id);
	}

	/**
	 * Creates the form container and a published page with the form as a
	 * content block.
	 *
	 * @return array{container: Page, page: Page}
	 */
	public static function create(): array {
		self::delete();
		$container = self::createContainer();

		$page = new Page();
		$page->template = 'default_page';
		$page->parent = wire('pages')->get(1);
		$page->name = self::PAGE_NAME;
		$page->title = 'Test Fixture Form Page';
		$page->of(false);
		wire('pages')->save($page, ['quiet' => true]);

		$block = $page->contents->getNew();
		$block->of(false);
		$block->setMatrixType('form');
		$block->form = $container;
		$block->save();
		$page->contents->add($block);
		wire('pages')->save($page, ['quiet' => true]);

		return ['container' => $container, 'page' => wire('pages')->getFresh($page->id)];
	}

	public static function delete(): void {
		self::loadGuard();
		$pages = wire('pages');
		foreach ($pages->find('name=' . self::PAGE_NAME . '|' . self::CONTAINER_NAME . ', include=all') as $page) {
			if ($page->template->name === 'forms_container') {
				FormSubmissionGuard::clearForm($page->id);
			}
			$pages->delete($page, true);
		}
	}

	public static function loadGuard(): void {
		require_once wire('config')->paths->templates . 'components/forms_component/form_submission_guard.class.php';
	}

	/**
	 * Number of stored submissions below the container, read from the database.
	 */
	public static function submissionCount(Page $container): int {
		return (int) wire('pages')->count('parent_id=' . $container->id . ', include=all');
	}
}
