<?php

declare(strict_types=1);
namespace Tests\Api;

use ProcessWire\Page;
use ProcessWire\PageAccessPassword;
use Tests\Support\AccessTokens;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * Password locked pages (module PageAccessPassword) on the routes
 * `tpage/{id|path}`, `page-unlock/{id}` and `file/{id}`: the locked answer
 * without access, the full answer with an unlock key, an account grant or as
 * editor, and the limit of failed attempts. Creates its own pages and users
 * and removes them afterwards.
 */
final class PageUnlockRoutesTest extends ApiTestCase {
	private const USER_NAME = 'test-fixture-locked-user';

	/** Cache prefix of the failed attempt counters (PageUnlockApi::ATTEMPT_CACHE_PREFIX). */
	private const ATTEMPT_CACHE_PREFIX = 'page-unlock-';

	/** Fields of the locked answer, for a project page with a date. */
	private const LOCKED_KEYS = [
		'id', 'name', 'title', 'created', 'modified', 'url', 'httpUrl', 'template', 'locked',
		'isProjectPage', 'project', 'project_id', 'color', 'datetime_from', 'breadcrumbs', 'language', 'seo', 'hash',
	];

	/** @var array<string, Page>|null */
	private static ?array $pages = null;

	private static string $password = '';

	private static string $otherPassword = '';

	protected function setUp(): void {
		parent::setUp();

		if (self::$pages === null) {
			self::$password = 'pw-' . bin2hex(random_bytes(8));
			self::$otherPassword = 'other-' . bin2hex(random_bytes(8));
			self::$pages = Fixtures::createLockedTree(self::$password, self::$otherPassword);
		}

		Fixtures::setLockedPassword(self::$pages['page'], self::$password);
		self::clearAttempts();
	}

	public static function tearDownAfterClass(): void {
		AccessTokens::deleteAll();
		if (self::$pages !== null) {
			self::clearAttempts();
			Fixtures::deleteLockedTree();
			self::$pages = null;
		}
		self::deleteUser();
		parent::tearDownAfterClass();
	}

	public function testALockedPageAnswersOnlyWithItsHeaderData(): void {
		$page = self::$pages['page'];

		foreach (['tpage/' . $page->id, 'tpage' . $page->path] as $route) {
			$response = $this->apiRequest('GET', $route);

			self::assertSame(200, $response['status'], $route . ': ' . $response['raw']);
			self::assertLockedAnswer($response, $route);

			$json = $response['json'];
			self::assertSame($page->id, $json['id']);
			self::assertSame('Test Fixture Locked Page', $json['title']);
			self::assertSame('default_page', $json['template']['name']);
			self::assertTrue($json['isProjectPage']);
			self::assertSame(self::$pages['project']->id, $json['project']['id']);
			self::assertSame(self::$pages['project']->id, $json['project_id']);
			self::assertSame(Fixtures::LOCKED_PROJECT_COLOR, $json['color']);
			self::assertSame(1700000000, $json['datetime_from']);
			self::assertSame($page->id, end($json['breadcrumbs'])['id']);
			$seo = self::assertSeoShape($json['seo'], $page->path);
			self::assertNull($seo['image']);
			self::assertTrue($seo['noindex']);
		}
	}

	public function testAValidKeyGivesTheFullPageWithTheKey(): void {
		$page = self::$pages['page'];
		$key = self::module()->createUnlockKey($page);
		$locked = $this->apiRequest('GET', 'tpage/' . $page->id);

		$response = $this->apiRequest('GET', 'tpage/' . $page->id . '?unlock=' . rawurlencode($key['unlock_key']));

		self::assertSame(200, $response['status'], $response['raw']);
		$json = $response['json'];
		self::assertFalse($json['locked']);
		self::assertSame($key['unlock_key'], $json['unlock_key'], 'A valid key is passed on unchanged, never extended.');
		self::assertSame($key['expires'], $json['expires']);
		self::assertSame(Fixtures::LOCKED_INTRO, $json['intro']);
		self::assertSame(Fixtures::LOCKED_MAIN_IMAGE, $json['main_image']['basename']);
		self::assertSame('image', $json['contents'][0]['type']);
		self::assertStringContainsString(Fixtures::LOCKED_ITEM_IMAGE, $response['raw']);

		// The app sends the hash of its last answer: after unlocking it must get the content, not 204.
		self::assertNotSame($locked['json']['hash'], $json['hash']);
		$afterLocked = $this->apiRequest('GET', 'tpage/' . $page->id . '?unlock=' . rawurlencode($key['unlock_key']) . '&hash=' . $locked['json']['hash']);
		self::assertSame(200, $afterLocked['status']);
		self::assertFalse($afterLocked['json']['locked']);
		$unchanged = $this->apiRequest('GET', 'tpage/' . $page->id . '?unlock=' . rawurlencode($key['unlock_key']) . '&hash=' . $json['hash']);
		self::assertSame(204, $unchanged['status'], 'Control: the hash of the unlocked answer still answers 204.');
	}

	public function testInvalidKeysGiveTheLockedPage(): void {
		$page = self::$pages['page'];
		$valid = self::module()->createUnlockKey($page)['unlock_key'];
		[$expires, $signature] = explode('.', $valid);
		$expiredAt = time() - 10;

		$keys = [
			'malformed' => '123.abc',
			'expired' => $expiredAt . '.' . self::signature($page, $expiredAt),
			'key of another locked page' => self::module()->createUnlockKey(self::$pages['other'])['unlock_key'],
			'changed signature' => $expires . '.' . substr($signature, 0, -1) . (substr($signature, -1) === 'A' ? 'B' : 'A'),
			'extended expiry' => ((int) $expires + 86400) . '.' . $signature,
		];
		foreach ($keys as $label => $key) {
			$response = $this->apiRequest('GET', 'tpage/' . $page->id . '?unlock=' . rawurlencode($key));
			self::assertSame(200, $response['status'], $label . ': ' . $response['raw']);
			self::assertLockedAnswer($response, $label);
		}

		$array = $this->apiRequest('GET', 'tpage/' . $page->id . '?unlock[]=' . rawurlencode($valid));
		self::assertSame(200, $array['status'], $array['raw']);
		self::assertLockedAnswer($array, 'key as array');

		$control = $this->apiRequest('GET', 'tpage/' . $page->id . '?unlock=' . rawurlencode($valid));
		self::assertFalse($control['json']['locked'], 'Control: the untouched key opens the page.');
	}

	public function testAnUnprotectedPageIsNotLockedAndHasNoKey(): void {
		$content = $this->apiRequest('GET', 'tpage/test-fixture-content');
		self::assertSame(200, $content['status'], $content['raw']);
		self::assertFalse($content['json']['locked']);
		self::assertArrayNotHasKey('unlock_key', $content['json']);
		self::assertArrayNotHasKey('expires', $content['json']);
		self::assertNotEmpty($content['json']['contents']);

		// The lock applies to the page itself, not to its children.
		$child = $this->apiRequest('GET', 'tpage/' . self::$pages['child']->id);
		self::assertSame(200, $child['status'], $child['raw']);
		self::assertFalse($child['json']['locked']);
		self::assertArrayNotHasKey('unlock_key', $child['json']);
		self::assertSame('Test Fixture Locked Child', $child['json']['title']);
	}

	public function testAHiddenLockedPageStaysNotFound(): void {
		$hidden = $this->apiRequest('GET', 'tpage/' . self::$pages['hidden']->id);
		self::assertSame(404, $hidden['status'], $hidden['raw']);
		self::assertSame('not_found_exception', $hidden['json']['errorcode'] ?? null);
	}

	public function testUnlockAnswersNotFoundForMissingHiddenAndUnprotectedPages(): void {
		$body = json_encode(['password' => self::$otherPassword]);
		$targets = [
			'missing' => 999999999,
			'hidden (unpublished, locked)' => self::$pages['hidden']->id,
			'not locked' => Fixtures::page(Fixtures::CONTENT_PATH)->id,
			'child of a locked page' => self::$pages['child']->id,
		];
		foreach ($targets as $label => $id) {
			$response = $this->unlock($id, $body);
			self::assertSame(404, $response['status'], $label . ': ' . $response['raw']);
			self::assertSame('not_found_exception', $response['json']['errorcode'] ?? null, $label);
			self::assertSame('Not Found.', $response['json']['error'] ?? null, $label);
		}
	}

	public function testUnlockWithoutPasswordIsABadRequest(): void {
		$page = self::$pages['page'];
		foreach (['no body' => null, 'empty object' => '{}', 'empty string' => '{"password":""}', 'null' => '{"password":null}', 'array' => '{"password":["x"]}'] as $label => $body) {
			$response = $this->unlock($page->id, $body);
			self::assertSame(400, $response['status'], $label . ': ' . $response['raw']);
			self::assertSame('missing_required_parameter', $response['json']['errorcode'] ?? null, $label);
			self::assertSame("Required parameter: 'password' missing!", $response['json']['error'] ?? null, $label);
		}

		// The password is read from the body only, never from the URL.
		$query = $this->apiRequest('POST', 'page-unlock/' . $page->id . '?password=' . rawurlencode(self::$password));
		self::assertSame(400, $query['status'], $query['raw']);
		self::assertSame(0, self::countAttempts($page), 'A missing password is no failed attempt.');
	}

	public function testAWrongPasswordIsForbiddenAndTheRightOneGivesAKey(): void {
		$page = self::$pages['page'];

		$wrong = $this->unlock($page->id, json_encode(['password' => self::$otherPassword]));
		self::assertSame(403, $wrong['status'], $wrong['raw']);
		self::assertSame('wrong_password', $wrong['json']['errorcode'] ?? null);
		self::assertStringNotContainsString(self::$otherPassword, $wrong['raw']);

		$right = $this->unlock($page->id, json_encode(['password' => self::$password]));
		self::assertSame(200, $right['status'], $right['raw']);
		self::assertSame(['unlock_key', 'expires'], array_keys($right['json']));
		self::assertMatchesRegularExpression('/^[0-9]+\.[A-Za-z0-9_-]{43}$/', $right['json']['unlock_key']);
		self::assertSame((int) explode('.', $right['json']['unlock_key'])[0], $right['json']['expires']);
		self::assertEqualsWithDelta(time() + PageAccessPassword::unlockKeyLifetime, $right['json']['expires'], 30);
		self::assertStringNotContainsString(self::$password, $right['raw']);

		$opened = $this->apiRequest('GET', 'tpage/' . $page->id . '?unlock=' . rawurlencode($right['json']['unlock_key']));
		self::assertFalse($opened['json']['locked']);
		self::assertSame(Fixtures::LOCKED_INTRO, $opened['json']['intro']);

		$guest = $this->apiRequest('GET', 'tpage/' . $page->id);
		self::assertTrue($guest['json']['locked'], 'Unlocking as guest grants nothing without the key.');
	}

	public function testPasswordsAreComparedExactlyAsStored(): void {
		$page = self::$pages['page'];
		$cases = [
			'umlauts and spaces at the edges' => '  Ümläut ß pässwört  ',
			'very long' => str_repeat('Ä1 ', 700) . 'end',
		];
		foreach ($cases as $label => $password) {
			Fixtures::setLockedPassword($page, $password);
			self::clearAttempts();

			$wrongs = [
				'trimmed' => trim($password),
				'without the last character' => mb_substr($password, 0, -1),
				'lower case' => mb_strtolower($password),
				'huge input' => str_repeat($password, 50),
			];
			foreach ($wrongs as $variant => $input) {
				if ($input === $password) {
					continue;
				}
				$response = $this->unlock($page->id, json_encode(['password' => $input]));
				self::assertSame(403, $response['status'], $label . ', ' . $variant . ': ' . substr($response['raw'], 0, 300));
			}

			$right = $this->unlock($page->id, json_encode(['password' => $password]));
			self::assertSame(200, $right['status'], $label . ': ' . substr($right['raw'], 0, 300));
		}
	}

	public function testTenFailedAttemptsBlockEvenTheRightPasswordUntilTheWindowHasPassed(): void {
		$page = self::$pages['page'];
		$wrongBody = json_encode(['password' => self::$otherPassword]);
		$rightBody = json_encode(['password' => self::$password]);

		for ($attempt = 1; $attempt <= 10; $attempt++) {
			$response = $this->unlock($page->id, $wrongBody);
			self::assertSame(403, $response['status'], 'attempt ' . $attempt . ': ' . $response['raw']);
		}
		self::assertSame(10, self::countAttempts($page));

		$blocked = $this->unlock($page->id, $rightBody);
		self::assertSame(429, $blocked['status'], $blocked['raw']);
		self::assertSame('too_many_requests', $blocked['json']['errorcode'] ?? null);
		self::assertArrayNotHasKey('unlock_key', $blocked['json']);
		$retryAfter = $blocked['headers']['retry-after'] ?? '';
		self::assertMatchesRegularExpression('/^[0-9]+$/', $retryAfter);
		self::assertGreaterThanOrEqual(1, (int) $retryAfter);
		self::assertLessThanOrEqual(3600, (int) $retryAfter);

		$blockedWrong = $this->unlock($page->id, $wrongBody);
		self::assertSame(429, $blockedWrong['status']);
		self::assertSame(10, self::countAttempts($page), 'Blocked requests are not counted.');

		// The counter is per page.
		$other = $this->unlock(self::$pages['other']->id, json_encode(['password' => self::$password]));
		self::assertSame(403, $other['status'], $other['raw']);

		// Move the counted attempts back by the length of the window.
		self::shiftAttempts($page, -3600);
		$afterWindow = $this->unlock($page->id, $rightBody);
		self::assertSame(200, $afterWindow['status'], $afterWindow['raw']);
	}

	public function testALoggedInUserGetsAGrantAndThenAccessWithoutKey(): void {
		$page = self::$pages['page'];
		$user = self::createUser();
		$authorization = AccessTokens::authorizationHeader($user->id);

		$before = $this->apiRequest('GET', 'tpage/' . $page->id, [$authorization]);
		self::assertTrue($before['json']['locked'], 'Without a grant the user has no access.');

		$unlock = $this->unlock($page->id, json_encode(['password' => self::$password]), [$authorization]);
		self::assertSame(200, $unlock['status'], $unlock['raw']);
		self::assertSame(1, self::countGrants($user->id, $page->id));

		$after = $this->apiRequest('GET', 'tpage/' . $page->id, [$authorization]);
		self::assertSame(200, $after['status'], $after['raw']);
		self::assertFalse($after['json']['locked']);
		self::assertSame(Fixtures::LOCKED_INTRO, $after['json']['intro']);
		self::assertTrue(self::module()->hasAccess($page, $after['json']['unlock_key']), 'The key in the answer is valid for file requests.');

		$guest = $this->apiRequest('GET', 'tpage/' . $page->id);
		self::assertTrue($guest['json']['locked'], 'The grant belongs to the user, not to the client.');
	}

	public function testAnEditorHasAccessWithoutKey(): void {
		$page = self::$pages['page'];
		$response = $this->apiRequest('GET', 'tpage/' . $page->id, [AccessTokens::authorizationHeader(AccessTokens::superuserId())]);

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertFalse($response['json']['locked']);
		self::assertSame(Fixtures::LOCKED_INTRO, $response['json']['intro']);
		self::assertTrue(self::module()->hasAccess($page, $response['json']['unlock_key']), 'The key in the answer is valid for file requests.');
		self::assertIsInt($response['json']['expires']);
	}

	public function testEditorsAndGrantsGetTheirValidKeyBackUnchanged(): void {
		$page = self::$pages['page'];
		$user = self::createUser();
		$grantAuthorization = AccessTokens::authorizationHeader($user->id);
		$unlock = $this->unlock($page->id, json_encode(['password' => self::$password]), [$grantAuthorization]);
		self::assertSame(200, $unlock['status'], $unlock['raw']);

		$key = self::module()->createUnlockKey($page);
		$route = 'tpage/' . $page->id . '?unlock=' . rawurlencode($key['unlock_key']);
		foreach ([
			'editor' => AccessTokens::authorizationHeader(AccessTokens::superuserId()),
			'account grant' => $grantAuthorization,
		] as $label => $authorization) {
			$first = $this->apiRequest('GET', $route, [$authorization]);
			self::assertSame(200, $first['status'], $label . ': ' . $first['raw']);
			self::assertFalse($first['json']['locked'], $label);
			self::assertSame($key['unlock_key'], $first['json']['unlock_key'], $label . ': the sent key comes back unchanged.');
			self::assertSame($key['expires'], $first['json']['expires'], $label);

			$second = $this->apiRequest('GET', $route . '&hash=' . $first['json']['hash'], [$authorization]);
			self::assertSame(204, $second['status'], $label . ': unchanged answer with the same key.');

			// Without a key a new one is created.
			$withoutKey = $this->apiRequest('GET', 'tpage/' . $page->id, [$authorization]);
			self::assertFalse($withoutKey['json']['locked'], $label);
			self::assertTrue(self::module()->hasAccess($page, $withoutKey['json']['unlock_key']), $label);
		}
	}

	public function testFilesOfALockedPageNeedTheKey(): void {
		$page = self::$pages['page'];
		$key = rawurlencode(self::module()->createUnlockKey($page)['unlock_key']);
		$otherKey = rawurlencode(self::module()->createUnlockKey(self::$pages['other'])['unlock_key']);

		foreach ([
			'repeater image' => 'file/' . self::$pages['item']->id . '?file=' . Fixtures::LOCKED_ITEM_IMAGE,
			'main image' => 'file/' . $page->id . '?file=' . Fixtures::LOCKED_MAIN_IMAGE,
		] as $label => $route) {
			$guest = $this->apiRequest('GET', $route);
			self::assertSame(404, $guest['status'], $label . ' without key');
			self::assertSame('not_found_exception', $guest['json']['errorcode'] ?? null, $label);

			$foreign = $this->apiRequest('GET', $route . '&unlock=' . $otherKey);
			self::assertSame(404, $foreign['status'], $label . ' with the key of another page');

			$unlocked = $this->apiRequest('GET', $route . '&unlock=' . $key);
			self::assertSame(200, $unlocked['status'], $label . ' with key: ' . substr($unlocked['raw'], 0, 300));
			self::assertSame([300, 200], array_slice(getimagesizefromstring($unlocked['raw']) ?: [], 0, 2), $label);
		}
	}

	/**
	 * @param array<int, string> $extraHeaders
	 * @return array{status: int, json: mixed, raw: string, headers: array<string, string>}
	 */
	private function unlock(int $id, ?string $body, array $extraHeaders = []): array {
		return $this->apiRequest('POST', 'page-unlock/' . $id, array_merge(['Content-Type: application/json'], $extraHeaders), $body);
	}

	/**
	 * @param array{status: int, json: mixed, raw: string, headers: array<string, string>} $response
	 */
	private static function assertLockedAnswer(array $response, string $label): void {
		$json = $response['json'];
		self::assertIsArray($json, $label);
		$keys = array_keys($json);
		sort($keys);
		$expected = self::LOCKED_KEYS;
		sort($expected);
		self::assertSame($expected, $keys, $label);
		self::assertTrue($json['locked'], $label);
		self::assertNull($json['seo']['image'], $label);
		self::assertTrue($json['seo']['noindex'], $label);
		foreach ([Fixtures::LOCKED_INTRO, Fixtures::LOCKED_MAIN_IMAGE, Fixtures::LOCKED_ITEM_IMAGE, 'test-fixture-locked-main', 'test-fixture-locked-item'] as $content) {
			self::assertStringNotContainsString($content, $response['raw'], $label);
		}
	}

	private static function module(): PageAccessPassword {
		return wire('modules')->get('PageAccessPassword');
	}

	private static function signature(Page $page, int $expires): string {
		$method = new \ReflectionMethod(self::module(), 'signature');
		$method->setAccessible(true);

		return $method->invoke(self::module(), $page, $expires);
	}

	/**
	 * Counters of failed attempts (WireCache rows of the server) of all clients for the page.
	 *
	 * @return array<string, array<int, int>>
	 */
	private static function attemptRows(Page $page): array {
		$query = wire('database')->prepare('SELECT `name`, `data` FROM `caches` WHERE `name` LIKE :name');
		$query->execute([':name' => self::ATTEMPT_CACHE_PREFIX . $page->id . '-%']);
		$rows = [];
		foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$rows[$row['name']] = array_map('intval', (array) json_decode($row['data'], true));
		}

		return $rows;
	}

	private static function countAttempts(Page $page): int {
		return array_sum(array_map('count', self::attemptRows($page)));
	}

	private static function shiftAttempts(Page $page, int $seconds): void {
		foreach (self::attemptRows($page) as $name => $times) {
			$query = wire('database')->prepare('UPDATE `caches` SET `data` = :data WHERE `name` = :name');
			$query->execute([':data' => json_encode(array_map(fn ($time) => $time + $seconds, $times)), ':name' => $name]);
		}
	}

	private static function clearAttempts(): void {
		if (self::$pages === null) {
			return;
		}
		foreach (['page', 'other', 'hidden'] as $key) {
			$query = wire('database')->prepare('DELETE FROM `caches` WHERE `name` LIKE :name');
			$query->execute([':name' => self::ATTEMPT_CACHE_PREFIX . self::$pages[$key]->id . '-%']);
		}
	}

	private static function countGrants(int $userId, int $pageId): int {
		$query = wire('database')->prepare('SELECT COUNT(*) FROM `' . PageAccessPassword::grantsTable . '` WHERE `user_id` = :user_id AND `page_id` = :page_id');
		$query->execute([':user_id' => $userId, ':page_id' => $pageId]);

		return (int) $query->fetchColumn();
	}

	private static function createUser(): \ProcessWire\User {
		self::deleteUser();
		$user = wire('users')->add(self::USER_NAME);
		self::assertTrue($user->id > 0 && !$user->isGuest() && !$user->isSuperuser(), 'The test needs a logged-in user without edit rights.');

		return $user;
	}

	private static function deleteUser(): void {
		foreach (wire('users')->find('name=' . self::USER_NAME . ', include=all') as $user) {
			wire('users')->delete($user);
		}
	}
}
