<?php

declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
use ProcessWire\Page;
use ProcessWire\PageAccessPassword;
use ProcessWire\User;
use ProcessWire\Wire404Exception;

use function ProcessWire\wire;

/**
 * Access to pages locked by PageAccessPassword: editors, logged in users with
 * a grant for the current password and anybody with a valid unlock key.
 * Files in repeaters follow the page that owns the repeater.
 */
final class PageAccessPasswordTest extends TestCase {
	private const PREFIX = 'test-fixture-page-password-';

	/** @var array<string, Page> */
	private static array $pages = [];

	/** @var array<string, string> */
	private static array $passwords = [];

	private PageAccessPassword $module;

	public static function setUpBeforeClass(): void {
		self::deleteFixtures();

		$root = wire('pages')->get('/');
		self::$passwords = [
			'a' => 'a-' . bin2hex(random_bytes(8)),
			'b' => 'b-' . bin2hex(random_bytes(8)),
		];
		self::$pages['a'] = self::newPage($root, 'a', 'Test Page Password A', self::$passwords['a']);
		self::$pages['b'] = self::newPage($root, 'b', 'Test Page Password B', self::$passwords['b']);
		self::$pages['open'] = self::newPage($root, 'open', 'Test Page Password Open', null);

		// A repeater item in the locked page, like the gallery blocks.
		$a = self::$pages['a'];
		$a->of(false);
		$item = $a->contents->getNew();
		$item->setMatrixType('text');
		$item->save();
		wire('pages')->save($a, ['quiet' => true]);
		self::$pages['item'] = $item;
	}

	public static function tearDownAfterClass(): void {
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
		self::deleteFixtures();
		self::$pages = [];
	}

	protected function setUp(): void {
		$this->module = wire('modules')->get('PageAccessPassword');
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
	}

	protected function tearDown(): void {
		wire('users')->setCurrentUser(wire('users')->getGuestUser());
		unset(wire('input')->get->unlock);
		$this->setPassword(self::$pages['a'], self::$passwords['a']);
		$this->setPassword(self::$pages['b'], self::$passwords['b']);
	}

	public function testAValidUnlockKeyGivesAccess(): void {
		$a = self::$pages['a'];
		self::assertTrue($this->module->isLocked($a));
		self::assertFalse($this->module->hasAccess($a), 'A guest without a key has no access.');
		self::assertFalse($this->module->hasAccess($a, 'not-a-key'));

		$key = $this->module->createUnlockKey($a);
		self::assertMatchesRegularExpression('/^[0-9]+\.[A-Za-z0-9_-]{43}$/', $key['unlock_key']);
		self::assertSame((int) explode('.', $key['unlock_key'])[0], $key['expires']);
		self::assertEqualsWithDelta(time() + 30 * 86400, $key['expires'], 5);
		self::assertTrue($this->module->hasAccess($a, $key['unlock_key']));
	}

	public function testAnExpiredKeyGivesNoAccess(): void {
		$a = self::$pages['a'];
		$expired = (time() - 10) . '.' . $this->signature($a, time() - 10);
		$valid = (time() + 100) . '.' . $this->signature($a, time() + 100);

		self::assertTrue($this->module->hasAccess($a, $valid), 'Control: a correctly signed key that has not expired.');
		self::assertFalse($this->module->hasAccess($a, $expired));
	}

	public function testATamperedKeyGivesNoAccess(): void {
		$a = self::$pages['a'];
		$key = $this->module->createUnlockKey($a)['unlock_key'];
		[$expires, $signature] = explode('.', $key);

		$lastChar = substr($signature, -1) === 'A' ? 'B' : 'A';
		self::assertFalse($this->module->hasAccess($a, $expires . '.' . substr($signature, 0, -1) . $lastChar), 'changed signature');
		self::assertFalse($this->module->hasAccess($a, ((int) $expires + 86400) . '.' . $signature), 'extended expiry');
		self::assertFalse($this->module->hasAccess($a, $expires . '.' . $signature . 'x'), 'appended character');
	}

	public function testAKeyStopsWorkingWhenThePasswordChanges(): void {
		$a = self::$pages['a'];
		$key = $this->module->createUnlockKey($a)['unlock_key'];
		self::assertTrue($this->module->hasAccess($a, $key));

		$this->setPassword($a, self::$passwords['a'] . '-changed');
		self::assertFalse($this->module->hasAccess($a, $key));
	}

	public function testAKeyOfOnePageDoesNotOpenAnother(): void {
		// Same password on both pages, so only the page id tells the keys apart.
		$this->setPassword(self::$pages['b'], self::$passwords['a']);
		$keyA = $this->module->createUnlockKey(self::$pages['a'])['unlock_key'];
		self::assertTrue($this->module->hasAccess(self::$pages['a'], $keyA));
		self::assertFalse($this->module->hasAccess(self::$pages['b'], $keyA));
	}

	public function testAnEditorHasAccessWithoutKey(): void {
		$superuser = $this->superuser();
		$a = self::$pages['a'];

		self::assertTrue($this->module->hasAccess($a, null, $superuser), 'Editor passed as user while the guest is current.');
		self::assertTrue(wire('user')->isGuest(), 'The current user is restored after the check for another user.');

		wire('users')->setCurrentUser($superuser);
		self::assertTrue($this->module->hasAccess($a));
		self::assertFalse($this->module->hasAccess($a, null, wire('users')->getGuestUser()), 'The guest passed as user has no access.');
	}

	public function testAGrantHoldsUntilThePasswordChanges(): void {
		$a = self::$pages['a'];
		$user = $this->newUser('grant');

		wire('users')->setCurrentUser($user);
		self::assertFalse($this->module->hasAccess($a), 'No access before the grant.');

		$this->module->grantForUser($a, $user);
		self::assertTrue($this->module->hasAccess($a));
		self::assertFalse($this->module->hasAccess(self::$pages['b']), 'The grant is for page A only.');
		self::assertFalse($this->module->hasAccess($a, null, wire('users')->getGuestUser()), 'The grant is for this user only.');

		$this->setPassword($a, self::$passwords['a'] . '-changed');
		self::assertFalse($this->module->hasAccess($a), 'The grant ends with the password change.');

		$this->module->grantForUser($a, $user);
		self::assertTrue($this->module->hasAccess($a), 'A new grant holds for the new password.');
	}

	public function testDeletingAUserRemovesItsGrants(): void {
		$user = $this->newUser('deleted-user');
		$this->module->grantForUser(self::$pages['a'], $user);
		$this->module->grantForUser(self::$pages['b'], $user);
		$userId = $user->id;
		self::assertSame(2, $this->countGrants('user_id', $userId));

		wire('users')->delete($user);
		self::assertSame(0, $this->countGrants('user_id', $userId));
	}

	public function testDeletingAPageRemovesItsGrants(): void {
		$page = self::newPage(wire('pages')->get('/'), 'deleted-page', 'Test Page Password Deleted', 'c-' . bin2hex(random_bytes(8)));
		$user = $this->newUser('page-grant');
		$this->module->grantForUser($page, $user);
		$this->module->grantForUser(self::$pages['a'], $user);
		$pageId = $page->id;
		self::assertSame(1, $this->countGrants('page_id', $pageId));

		wire('pages')->delete($page, true);
		self::assertSame(0, $this->countGrants('page_id', $pageId));
		self::assertSame(1, $this->countGrants('user_id', $user->id), 'Grants of other pages stay.');
	}

	public function testRepeaterItemsBelongToTheirPage(): void {
		$item = self::$pages['item'];
		self::assertNotSame(self::$pages['a']->id, $item->id);
		self::assertSame(self::$pages['a']->id, $this->module->getAccessPage($item)->id);
		self::assertSame(self::$pages['open']->id, $this->module->getAccessPage(self::$pages['open'])->id);

		self::assertFalse($this->module->isLocked($item), 'The repeater page itself has no password.');
		self::assertFalse($this->module->hasAccess($item));
		self::assertTrue($this->module->hasAccess($item, $this->module->createUnlockKey(self::$pages['a'])['unlock_key']));
		self::assertFalse($this->module->hasAccess($item, $this->module->createUnlockKey(self::$pages['b'])['unlock_key']));
		self::assertTrue($this->module->hasAccess(self::$pages['open']), 'Pages without password are open.');
	}

	public function testTheFileHookFollowsTheOwnerPage(): void {
		$item = self::$pages['item'];

		try {
			$this->callSendFileHook($item);
			self::fail('Expected a 404 for a file of a locked page without key.');
		} catch (Wire404Exception $e) {
			self::assertSame(Wire404Exception::codeFile, $e->getCode());
		}

		wire('input')->get->unlock = $this->module->createUnlockKey(self::$pages['a'])['unlock_key'];
		$this->callSendFileHook($item);
		$this->addToAssertionCount(1);
	}

	public function testThePasswordIsComparedExactlyAsStored(): void {
		$a = self::$pages['a'];
		$password = '  Grün Öl ß  ';
		$this->setPassword($a, $password);
		wire('pages')->uncache($a);
		$stored = wire('pages')->get($a->id)->getUnformatted('pageaccess_password');
		self::assertSame($password, $stored, 'The text field keeps the spaces at the edges.');

		self::assertTrue($this->module->checkPassword($a, $password));
		self::assertFalse($this->module->checkPassword($a, trim($password)));
		self::assertFalse($this->module->checkPassword($a, ' grün öl ß  '));
		self::assertFalse($this->module->checkPassword($a, ''));
		self::assertFalse($this->module->checkPassword(self::$pages['open'], ''), 'A page without password has nothing to compare.');
	}

	public function testListsLeaveLockedPagesOutForEverybody(): void {
		$service = wire('modules')->get('Twack')->getService('PagesService');
		$selector = [['name', '^=', self::PREFIX]];

		foreach (['guest' => wire('users')->getGuestUser(), 'superuser' => $this->superuser()] as $role => $user) {
			wire('users')->setCurrentUser($user);
			$ids = array_map(fn ($page) => $page->id, $service->getResults([], $selector)->items->getArray());
			self::assertContains(self::$pages['open']->id, $ids, 'Control: the open page is listed for ' . $role . '.');
			self::assertNotContains(self::$pages['a']->id, $ids, 'Locked page listed for ' . $role . '.');
			self::assertFalse(self::$pages['a']->listable(), 'listable() for ' . $role);
			self::assertTrue(self::$pages['open']->listable(), 'Control listable() for ' . $role);
		}
	}

	private function callSendFileHook(Page $page): void {
		$event = new HookEvent(['object' => wire('modules')->get('ProcessPageView'), 'method' => 'sendFile', 'arguments' => [$page, 'image.jpg']]);
		$this->module->hookProcessPageViewSendFile($event);
	}

	private function signature(Page $page, int $expires): string {
		$method = new \ReflectionMethod($this->module, 'signature');
		$method->setAccessible(true);

		return $method->invoke($this->module, $page, $expires);
	}

	private function countGrants(string $column, int $id): int {
		$query = wire('database')->prepare('SELECT COUNT(*) FROM `' . PageAccessPassword::grantsTable . '` WHERE `' . $column . '` = :id');
		$query->bindValue(':id', $id, \PDO::PARAM_INT);
		$query->execute();

		return (int) $query->fetchColumn();
	}

	private function setPassword(Page $page, string $password): void {
		$page->of(false);
		$page->pageaccess_password = $password;
		wire('pages')->save($page, ['quiet' => true]);
	}

	private function newUser(string $suffix): User {
		$name = self::PREFIX . $suffix;
		$existing = wire('users')->get('name=' . $name . ', include=all');
		if ($existing->id) {
			wire('users')->delete($existing);
		}
		$user = wire('users')->add($name);
		self::assertTrue($user->id > 0 && !$user->isGuest() && !$user->isSuperuser());

		return $user;
	}

	private function superuser(): User {
		$user = wire('users')->get('roles=superuser, sort=id');
		self::assertTrue($user instanceof User && $user->id > 0 && $user->isSuperuser(), 'The test needs a superuser.');

		return $user;
	}

	private static function newPage(Page $parent, string $suffix, string $title, ?string $password): Page {
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
		wire('pages')->save($page, ['quiet' => true]);

		return $page;
	}

	private static function deleteFixtures(): void {
		$pages = wire('pages');
		foreach ($pages->find('parent=1, name^=' . self::PREFIX . ', include=all') as $page) {
			$pages->delete($page, true);
		}
		foreach (wire('users')->find('name^=' . self::PREFIX . ', include=all') as $user) {
			wire('users')->delete($user);
		}
	}
}
