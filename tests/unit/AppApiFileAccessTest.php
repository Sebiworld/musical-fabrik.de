<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\AppApiFile;
use ProcessWire\BadRequestException;
use ProcessWire\HookEvent;
use ProcessWire\NotFoundException;
use ProcessWire\Page;
use ProcessWire\PageAccessPassword;
use ProcessWire\User;

use function ProcessWire\wire;

/**
 * Access check of the AppApi file endpoint (/file/{id}), called in-process
 * as guest. A request without "file" parameter tells the two outcomes apart:
 * a page whose files are accessible gets as far as the file name
 * (BadRequestException), a refused page ends with a NotFoundException
 * before that.
 */
final class AppApiFileAccessTest extends TestCase {
	private const PREFIX = 'test-fixture-appapi-file-';

	/** @var array<string, Page> */
	private static array $pages = [];

	private PageAccessPassword $passwordModule;

	/** @var string[] */
	private array $hookIds = [];

	public static function setUpBeforeClass(): void {
		require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';
		self::deleteFixtures();

		$root = wire('pages')->get('/');
		self::$pages['open'] = self::newPage($root, 'open', 'Test AppApi File Open', null, false);
		self::$pages['hidden'] = self::newPage($root, 'hidden', 'Test AppApi File Hidden', null, true);
		self::$pages['locked'] = self::newPage($root, 'locked', 'Test AppApi File Locked', 'a-' . bin2hex(random_bytes(8)), false);
		self::$pages['other'] = self::newPage($root, 'other', 'Test AppApi File Other', 'b-' . bin2hex(random_bytes(8)), false);

		foreach (['open', 'hidden', 'locked'] as $key) {
			self::$pages[$key . '-item'] = self::newRepeaterItem(self::$pages[$key]);
		}
	}

	public static function tearDownAfterClass(): void {
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
		self::deleteFixtures();
		self::$pages = [];
	}

	protected function setUp(): void {
		$this->passwordModule = wire('modules')->get('PageAccessPassword');
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
		unset(wire('input')->get->file, wire('input')->get->unlock);
	}

	protected function tearDown(): void {
		foreach ($this->hookIds as $hookId) {
			wire('modules')->get('AppApiFile')->removeHook($hookId);
		}
		$this->hookIds = [];
		unset(wire('input')->get->unlock);
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
	}

	public function testVisiblePagesAreAccessibleAndHiddenPagesAreNot(): void {
		self::assertTrue(self::$pages['open']->viewable('', false), 'Control: the open page is viewable for guests.');
		self::assertFalse(self::$pages['hidden']->viewable('', false), 'Control: the unpublished page is not viewable for guests.');

		$this->assertAccessible('open');
		$this->assertAccessible('open-item');
		$this->assertNotFound('hidden');
		$this->assertNotFound('hidden-item');

		$module = wire('modules')->get('AppApiFile');
		self::assertTrue($module->isFileAccessible(self::$pages['open-item'], self::$pages['open']));
		self::assertFalse($module->isFileAccessible(self::$pages['hidden-item'], self::$pages['hidden']));
	}

	public function testAHookReturningFalseGivesNotFound(): void {
		$this->assertAccessible('open', 'Control before the hook.');

		$received = [];
		$this->hookIds[] = wire('modules')->get('AppApiFile')->addHookAfter('isFileAccessible', function (HookEvent $event) use (&$received) {
			$received = [$event->arguments(0)->id, $event->arguments(1)->id];
			$event->return = false;
		});

		$this->assertNotFound('open');
		$this->assertNotFound('open-item');
		self::assertSame([self::$pages['open-item']->id, self::$pages['open']->id], $received, 'The hook gets the file page and the page that decides the access.');
	}

	public function testFilesOfALockedPageNeedTheKeyOfThatPage(): void {
		self::assertTrue(self::$pages['locked']->viewable('', false), 'Control: the locked page is viewable, only the password protects it.');

		foreach (['locked', 'locked-item'] as $key) {
			$this->assertNotFound($key, 'without key');

			wire('input')->get->unlock = '123.abc';
			$this->assertNotFound($key, 'with a wrong key');

			wire('input')->get->unlock = $this->passwordModule->createUnlockKey(self::$pages['other'])['unlock_key'];
			$this->assertNotFound($key, 'with the key of another locked page');

			wire('input')->get->unlock = $this->passwordModule->createUnlockKey(self::$pages['locked'])['unlock_key'];
			$this->assertAccessible($key, 'with the key of the owner page');

			unset(wire('input')->get->unlock);
		}
	}

	public function testAnEditorGetsFilesOfALockedPageWithoutKey(): void {
		$superuser = wire('users')->get('roles=superuser, sort=id');
		self::assertTrue($superuser instanceof User && $superuser->isSuperuser(), 'The test needs a superuser.');

		wire('users')->setCurrentUser($superuser);
		$this->assertAccessible('locked-item');
	}

	public function testOpenPagesStayAccessibleWithAnyUnlockParameter(): void {
		wire('input')->get->unlock = '123.abc';
		$this->assertAccessible('open');
		$this->assertAccessible('open-item');
		$this->assertNotFound('hidden', 'A key does not open a page that is not viewable.');

		wire('input')->get->unlock = $this->passwordModule->createUnlockKey(self::$pages['locked'])['unlock_key'];
		$this->assertNotFound('hidden-item', 'A valid key of a locked page does not open a hidden page.');
	}

	private function assertAccessible(string $key, string $message = ''): void {
		try {
			AppApiFile::pageIDFileRequest((object) ['id' => self::$pages[$key]->id]);
			self::fail($key . ': expected the request to reach the file name check. ' . $message);
		} catch (BadRequestException $e) {
			self::assertSame('No valid filename.', $e->getMessage(), $key . ' ' . $message);
		} catch (NotFoundException $e) {
			self::fail($key . ': refused with 404, expected access. ' . $message);
		}
	}

	private function assertNotFound(string $key, string $message = ''): void {
		try {
			AppApiFile::pageIDFileRequest((object) ['id' => self::$pages[$key]->id]);
			self::fail($key . ': expected a NotFoundException. ' . $message);
		} catch (NotFoundException $e) {
			self::assertSame(404, $e->getCode(), $key . ' ' . $message);
		} catch (BadRequestException $e) {
			self::fail($key . ': access was granted, expected 404. ' . $message);
		}
	}

	private static function newPage(Page $parent, string $suffix, string $title, ?string $password, bool $unpublished): Page {
		$page = new Page();
		$page->template = 'default_page';
		$page->parent = $parent;
		$page->name = self::PREFIX . $suffix;
		$page->title = $title;
		$page->of(false);
		if ($password !== null) {
			$page->pageaccess_password_activate = 1;
			$page->pageaccess_password = $password;
		}
		if ($unpublished) {
			$page->addStatus(Page::statusUnpublished);
		}
		wire('pages')->save($page, ['quiet' => true]);

		return $page;
	}

	private static function newRepeaterItem(Page $page): Page {
		$page->of(false);
		$item = $page->contents->getNew();
		$item->setMatrixType('text');
		$item->save();
		wire('pages')->save($page, ['quiet' => true]);

		return $item;
	}

	private static function deleteFixtures(): void {
		$pages = wire('pages');
		foreach ($pages->find('parent=1, name^=' . self::PREFIX . ', include=all') as $page) {
			$pages->delete($page, true);
		}
	}
}
