<?php
namespace ProcessWire;

/**
 * Locks pages behind a password.
 *
 * A page is locked when its own password protection is active (no
 * inheritance to children). Files in repeaters belong to the page that owns
 * the repeater (see getAccessPage()).
 *
 * Access to a locked page has whoever
 * - may edit the page,
 * - is logged in and holds a grant for the current password (grantForUser()), or
 * - sends a valid unlock key (createUnlockKey()).
 *
 * Changing the password invalidates all unlock keys and grants of the page,
 * because both are bound to a fingerprint of the current password.
 */
class PageAccessPassword extends WireData implements Module {
	const module_tags = 'Page-Access';
	const fieldnames  = ['pageaccess_password_activate', 'pageaccess_password'];

	const grantsTable = 'pageaccess_password_grants';

	/** Lifetime of an unlock key in seconds (30 days). */
	const unlockKeyLifetime = 2592000;

	public static function getModuleInfo() {
		return [
			'title'    => __('Page Access Password'),
			'author'   => 'Sebastian Schendel',
			'version'  => '1.1.0',
			'summary'  => __('Enables you to set password that a user must type in to see a page.'),
			'singular' => true,
			'autoload' => true,
			'icon'     => 'unlock-alt',
			'requires' => ['PHP>=5.5.3', 'ProcessWire>=3.0.0']
		];
	}

	public function ___install() {
		$flags = Field::flagSystem + Field::flagAccessAPI + Field::flagAutojoin;

		$field        = new Field();
		$field->type  = $this->modules->get('FieldtypeCheckbox');
		$field->name  = 'pageaccess_password_activate';
		$field->label = $this->_('Limit access for a password?');
		$field->tags  = self::module_tags;
		$field->flags = $flags;
		$field->save();

		$field             = new Field();
		$field->type       = $this->modules->get('FieldtypeText');
		$field->name       = 'pageaccess_password';
		$field->label      = $this->_('Accessable with password:');
		$field->tags       = self::module_tags;
		$field->flags      = $flags;
		$field->showIf     = 'pageaccess_password_activate=1';
		$field->requiredIf = 'pageaccess_password_activate=1';
		$field->save();

		$this->createDBTables();
	}

	public function ___upgrade($fromVersion, $toVersion) {
		// Idempotent, so it is safe for every upgrade path.
		$this->createDBTables();
	}

	public function ___uninstall() {
		// Remove releasetime-fields:
		foreach (self::fieldnames as $fieldname) {
			$field = $this->wire('fields')->get($fieldname);
			if (!($field instanceof Field) || $field->name != $fieldname) {
				continue;
			}

			$field->flags = Field::flagSystemOverride;
			$field->flags = 0;
			$field->save();

			foreach ($this->wire('templates') as $template) {
				if (!$template->hasField($fieldname)) {
					continue;
				}
				$template->fieldgroup->remove($field);
				$template->fieldgroup->save();
			}

			$this->wire('fields')->delete($field);
		}

		$this->wire('database')->exec('DROP TABLE IF EXISTS `' . self::grantsTable . '`');
	}

	protected function createDBTables() {
		$this->wire('database')->exec('CREATE TABLE IF NOT EXISTS `' . self::grantsTable . '` (
			`user_id` INT UNSIGNED NOT NULL,
			`page_id` INT UNSIGNED NOT NULL,
			`fingerprint` CHAR(64) NOT NULL,
			`created` DATETIME NOT NULL,
			PRIMARY KEY (`user_id`, `page_id`),
			KEY `page_id` (`page_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
	}

	protected static $defaults = [
		'autoAdd'   => 0,
		'templates' => []
	];

	public static function getModuleConfigInputfields(array $data) {
		$form  = new InputfieldWrapper();

		return $form;
	}

	public function init() {
		// Move password-fields to settings-tab
		$this->addHookAfter('ProcessPageEdit::buildForm', $this, 'moveFieldToSettings');

		// Locked pages are never listed, not even for users with access:
		$this->addHook('Page::listable', $this, 'hookPageListable');

		// Manage access to files ($config->pagefileSecure has to be true)
		$this->addHookAfter('Page::isPublic', $this, 'hookPageIsPublic');
		$this->addHookBefore('ProcessPageView::sendFile', $this, 'hookProcessPageViewSendFile');

		// Files through the AppApi file endpoint (AppApiFile >= 2.1.0). Without the module the hook never runs.
		$this->addHookAfter('AppApiFile::isFileAccessible', $this, 'hookAppApiFileIsFileAccessible');

		// Remove the grants of deleted pages and users (users are pages, too):
		$this->addHookAfter('Pages::deleted', $this, 'hookPagesDeleted');
	}

	public function hookPageListable(HookEvent $event) {
		$page     = $event->object;
		$listable = $event->return;

		if ($listable && $this->isLocked($page)) {
			$listable = false;
		}
		$event->return = $listable;
	}

	/**
	 * if Page::isPublic() returns false a prefix (-) will be added to the name of the assets directory
	 * the directory is not accessible directly anymore
	 *
	 * @see https://processwire.com/talk/topic/15622-pagefilesecure-and-pageispublic-hook-not-working/
	 */
	public function hookPageIsPublic(HookEvent $event) {
		$page = $event->object;
		if ($event->return && $this->isPasswordProtectionActivated($page)) {
			$event->return = false;
		}
	}

	/**
	 * ProcessPageView::sendFile() is called only if the file is not directly accessible
	 * if this function is called AND the page is not public it passthru the protected file path (.htaccess) by default
	 * therefore we need this hook too
	 *
	 * The access is decided on the page that owns the file (for repeaters the
	 * page that holds the repeater field). An unlock key is read from the
	 * query parameter "unlock".
	 *
	 * @see https://processwire.com/talk/topic/15622-pagefilesecure-and-pageispublic-hook-not-working/
	 */
	public function hookProcessPageViewSendFile(HookEvent $event) {
		$page = $event->arguments(0);
		if (!$page instanceof Page) {
			return;
		}

		$unlockKey = $this->wire('input')->get('unlock');
		if (!is_string($unlockKey) || $unlockKey === '') {
			$unlockKey = null;
		}

		if (!$this->hasAccess($page, $unlockKey)) {
			throw new Wire404Exception($this->_('File not found'), Wire404Exception::codeFile);
		}
	}

	/**
	 * Files of locked pages through the AppApi file endpoint: no access
	 * means 404, the same as for pages that are not viewable. The access is
	 * decided on the page that owns the file, an unlock key is read from the
	 * query parameter "unlock". A refusal of an earlier check stays.
	 */
	public function hookAppApiFileIsFileAccessible(HookEvent $event) {
		if (!$event->return) {
			return;
		}

		$page = $event->arguments(0);
		if (!$page instanceof Page) {
			return;
		}

		$unlockKey = $this->wire('input')->get('unlock');
		if (!is_string($unlockKey) || $unlockKey === '') {
			$unlockKey = null;
		}

		if (!$this->hasAccess($page, $unlockKey)) {
			$event->return = false;
		}
	}

	public function hookPagesDeleted(HookEvent $event) {
		$page = $event->arguments(0);
		if (!$page instanceof Page || !$page->id) {
			return;
		}

		try {
			$this->executeGrantsQuery('DELETE FROM `' . self::grantsTable . '` WHERE `page_id` = :page_id OR `user_id` = :user_id', [
				':page_id' => (int) $page->id,
				':user_id' => (int) $page->id
			]);
		} catch (\Exception $e) {
			// Deleting the page must not fail because of the grants, but the leftover has to be visible.
			$this->wire('log')->error('PageAccessPassword: could not remove the grants of page ' . $page->id . ': ' . $e->getMessage());
		}
	}

	/**
	 * Does the page itself have an active password protection?
	 */
	public function isLocked(Page $page): bool {
		return $this->isPasswordProtectionActivated($page);
	}

	/**
	 * Does the page have an activated password-field?
	 * @param  Page    $page
	 * @return boolean
	 */
	public function isPasswordProtectionActivated(Page $page) {
		if ($page->template->hasField('pageaccess_password') && (!$page->template->hasField('pageaccess_password_activate') || $page->pageaccess_password_activate == true)) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the page that decides the access: the page itself, or for a
	 * repeater page the page that owns the repeater (following nested
	 * repeaters up to the first page that is not a repeater page).
	 */
	public function getAccessPage(Page $page): Page {
		$accessPage = $page;
		// The limit only guards against a broken repeater chain.
		for ($i = 0; $i < 20 && $accessPage instanceof RepeaterPage; $i++) {
			$forPage = $accessPage->getForPage();
			if (!$forPage instanceof Page || !$forPage->id) {
				break;
			}
			$accessPage = $forPage;
		}

		return $accessPage;
	}

	/**
	 * May the user see the content of the page? True for pages that are not
	 * locked. Does not replace Page::viewable(), it comes on top of it.
	 *
	 * @param Page        $page      the page or a repeater page in it
	 * @param string|null $unlockKey key from createUnlockKey()
	 * @param User|null   $user      defaults to the current user
	 */
	public function hasAccess(Page $page, ?string $unlockKey = null, ?User $user = null): bool {
		$page = $this->getAccessPage($page);
		if (!$this->isLocked($page)) {
			return true;
		}

		if (!$user instanceof User || !$user->id) {
			$user = $this->wire('user');
		}

		if ($this->canEdit($page, $user)) {
			return true;
		}

		if ($user->isLoggedin() && $this->hasGrant($page, $user)) {
			return true;
		}

		if ($unlockKey !== null && $this->isValidUnlockKey($page, $unlockKey)) {
			return true;
		}

		return false;
	}

	/**
	 * Is the password the current password of the locked page? The comparison
	 * is exact (no trimming) and takes the same time for every input. False
	 * for pages that are not locked or have no password.
	 */
	public function checkPassword(Page $page, string $password): bool {
		$page = $this->getAccessPage($page);
		if (!$this->isLocked($page)) {
			return false;
		}

		$stored = $this->getStoredPassword($page);
		if ($stored === '' || $password === '') {
			return false;
		}

		return hash_equals($this->fingerprint($stored), $this->fingerprint($password));
	}

	/**
	 * Creates an unlock key for the locked page, valid for 30 days or until
	 * the password changes.
	 *
	 * @return array{unlock_key: string, expires: int}
	 * @throws WireException if the page is not locked
	 */
	public function createUnlockKey(Page $page): array {
		$page = $this->getAccessPage($page);
		if (!$this->isLocked($page)) {
			throw new WireException('Unlock keys exist only for locked pages.');
		}

		$expires = time() + self::unlockKeyLifetime;

		return [
			'unlock_key' => $expires . '.' . $this->signature($page, $expires),
			'expires'    => $expires
		];
	}

	/**
	 * Grants the logged in user access to the locked page until its password
	 * changes. Replaces an earlier grant of the user for this page.
	 *
	 * @throws WireException if the user is not logged in or the page is not locked
	 */
	public function grantForUser(Page $page, User $user): void {
		$page = $this->getAccessPage($page);
		if (!$this->isLocked($page)) {
			throw new WireException('Grants exist only for locked pages.');
		}
		if (!$user->id || $user->isGuest()) {
			throw new WireException('Grants need a logged in user.');
		}

		$this->executeGrantsQuery('INSERT INTO `' . self::grantsTable . '` (`user_id`, `page_id`, `fingerprint`, `created`)
			VALUES (:user_id, :page_id, :fingerprint, NOW())
			ON DUPLICATE KEY UPDATE `fingerprint` = VALUES(`fingerprint`), `created` = VALUES(`created`)', [
			':user_id'     => (int) $user->id,
			':page_id'     => (int) $page->id,
			':fingerprint' => $this->fingerprint($this->getStoredPassword($page))
		]);
	}

	protected function hasGrant(Page $page, User $user): bool {
		$query = $this->executeGrantsQuery('SELECT `fingerprint` FROM `' . self::grantsTable . '` WHERE `user_id` = :user_id AND `page_id` = :page_id', [
			':user_id' => (int) $user->id,
			':page_id' => (int) $page->id
		]);
		$fingerprint = $query->fetchColumn();
		$query->closeCursor();

		if (!is_string($fingerprint) || $fingerprint === '') {
			return false;
		}

		return hash_equals($this->fingerprint($this->getStoredPassword($page)), $fingerprint);
	}

	/**
	 * Runs a query on the grants table. If the table is missing (the module
	 * upgrade has not run yet after a deployment), it is created and the
	 * query is run once more.
	 */
	protected function executeGrantsQuery(string $sql, array $values): \PDOStatement {
		$database = $this->wire('database');
		for ($attempt = 1; ; $attempt++) {
			$query = $database->prepare($sql);
			foreach ($values as $name => $value) {
				$query->bindValue($name, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
			}
			try {
				$query->execute();

				return $query;
			} catch (\PDOException $e) {
				if ($attempt > 1 || $e->getCode() !== '42S02') {
					throw $e;
				}
				$this->wire('log')->save('page-access-password', 'The table ' . self::grantsTable . ' was missing and has been created. Refresh the modules to run the module upgrade.');
				$this->createDBTables();
			}
		}
	}

	protected function isValidUnlockKey(Page $page, string $unlockKey): bool {
		if (!preg_match('/^([0-9]{1,12})\.([A-Za-z0-9_-]{43})$/', $unlockKey, $matches)) {
			return false;
		}

		$expires = (int) $matches[1];
		if ($expires < time()) {
			return false;
		}

		return hash_equals($this->signature($page, $expires), $matches[2]);
	}

	protected function signature(Page $page, int $expires): string {
		$data = $page->id . '|' . $expires . '|' . $this->fingerprint($this->getStoredPassword($page));
		$raw  = hash_hmac('sha256', $data, $this->getSecret(), true);

		return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
	}

	protected function fingerprint(string $password): string {
		return hash_hmac('sha256', $password, $this->getSecret());
	}

	/**
	 * The password exactly as stored, without text formatters.
	 */
	protected function getStoredPassword(Page $page): string {
		$password = $page->getUnformatted('pageaccess_password');

		return is_string($password) ? $password : '';
	}

	/**
	 * $config->pageUnlockSecret, or derived from $config->userAuthSalt if it
	 * is not set.
	 *
	 * @throws WireException if neither is set
	 */
	protected function getSecret(): string {
		$config = $this->wire('config');

		$secret = $config->pageUnlockSecret;
		if (is_string($secret) && $secret !== '') {
			return $secret;
		}

		$salt = $config->userAuthSalt;
		if (is_string($salt) && $salt !== '') {
			return hash_hmac('sha256', 'page-unlock', $salt);
		}

		throw new WireException('Neither $config->pageUnlockSecret nor $config->userAuthSalt is set.');
	}

	/**
	 * Page::editable() for the given user, which may differ from the current user.
	 */
	protected function canEdit(Page $page, User $user): bool {
		$currentUser = $this->wire('user');
		if ($currentUser instanceof User && $currentUser->id === $user->id) {
			return $page->editable();
		}

		$users = $this->wire('users');
		$users->setCurrentUser($user);
		try {
			return $page->editable();
		} finally {
			$users->setCurrentUser($currentUser);
		}
	}

	/**
	 * Moves the modules fields to the settings-tab
	 * @param  HookEvent $event
	 */
	public function moveFieldToSettings(HookEvent $event) {
		$form = $event->return;

		$settings = $form->find('id=ProcessPageEditSettings')->first();
		if (!$settings) {
			return;
		}

		foreach (self::fieldnames as $fieldname) {
			$field = $form->find('name=' . $fieldname)->first();
			if (!$field) {
				continue;
			}

			$form->remove($field);
			$settings->append($field);
		}
	}
}
