<?php
namespace ProcessWire;

class PageAccessPermissions extends WireData implements Module {

	const module_tags = 'Page-Access';
	const fieldnames = array('pageaccess_permissions_activate', 'pageaccess_permissions');

	public static function getModuleInfo() {
		return array(
			'title' => __('Page Access Permissions'),
			'author' => 'Sebastian Schendel',
			'version' => '1.0.0',
			'summary' => __('Enables you to set permissions that a user must have to see a page.'),
			'singular' => true,
			'autoload' => true,
			'icon' => 'unlock-alt',
			'requires' => array('PHP>=5.5.3', 'ProcessWire>=3.0.0'),
			'installs' => 'FieldtypePageAccessPermissions',
		);
	}

	public function ___install(){
		$flags = Field::flagSystem + Field::flagAccessAPI + Field::flagAutojoin;

		$field = new Field();
		$field->type = $this->modules->get("FieldtypeCheckbox");
		$field->name = 'pageaccess_permissions_activate';
		$field->label = $this->_('Limit access for special permissions?');
		$field->tags = self::module_tags;
		$field->flags = $flags;
		$field->save();

		$field = new Field();
		$field->type = $this->modules->get("FieldtypePageAccessPermissions");
		$field->name = 'pageaccess_permissions';
		$field->label = $this->_('Accessable with permissions:');
		$field->tags = self::module_tags;
		$field->flags = $flags;
		$field->showIf = 'pageaccess_permissions_activate=1';
		$field->requiredIf = 'pageaccess_permissions_activate=1';
		$field->save();
	}

	public function ___uninstall(){
		// Remove releasetime-fields:
		foreach(self::fieldnames as $fieldname){
			$field = $this->wire('fields')->get($fieldname);
			if(!($field instanceof Field) || $field->name != $fieldname) continue;

			$field->flags = Field::flagSystemOverride;
			$field->flags = 0;
			$field->save();

			foreach($this->wire('templates') as $template){
				if(!$template->hasField($fieldname)) continue;
				$template->fieldgroup->remove($field);
				$template->fieldgroup->save();
			}

			$this->wire('fields')->delete($field);
		}
	}

	protected static $defaults = array(
		'autoAdd' => 0,
		'templates' => []
	);

	public static function getModuleConfigInputfields(array $data) {
		$form  = new InputfieldWrapper();

		return $form;
	}

	public function init() {
		// Move releasetime-fields to settings-tab
		$this->addHookAfter("ProcessPageEdit::buildForm", $this, "moveFieldToSettings");

		// Prevent unreleased pagse from being viewed
		$this->addHook('Page::viewable', $this, 'hookPageViewable');

		// Unreleased pages cannot be listed:
		$this->addHook('Page::listable', $this, 'hookPageListable');

		// Manage access to files ($config->pagefileSecure has to be true)
		$this->addHookAfter('Page::isPublic', $this, 'hookPageIsPublic');
		$this->addHookBefore('ProcessPageView::sendFile', $this, 'hookProcessPageViewSendFile');
	}

	/**
	 * Hook for Page::viewable() or Page::viewable($user) method
	 *
	 * Is the page viewable by the current user? (or specified user)
	 * Optionally specify $user object to hook as first argument to check for a specific User.
	 * Optionally specify a field name (or Field object) to hook as first argument to check for specific field.
	 * Optionally specify boolean false as first or second argument to hook to bypass template filename check.
	 *
	 * @param HookEvent $event
	 *
	 */
	public function hookPageViewable(HookEvent $event) {
		$page = $event->object;
		$viewable = $event->return;

		if($viewable){
			// If the page would be viewable, additionally check Releasetime and User-Permission
			$viewable = $this->canUserSee($page);
		}
		$event->return = $viewable;
	}

	public function hookPageListable(HookEvent $event) {
		$page = $event->object;
		$listable = $event->return;

		if($listable){
			// If the page would be listable, additionally check Permissions
			$listable = $this->canUserSee($page);
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
		if($event->return && $this->arePermissionsActivated($page)) {
			$event->return = false;
		}
	}

	/**
	 * ProcessPageView::sendFile() is called only if the file is not directly accessible
	 * if this function is called AND the page is not public it passthru the protected file path (.htaccess) by default
	 * therefore we need this hook too
	 *
	 * @see https://processwire.com/talk/topic/15622-pagefilesecure-and-pageispublic-hook-not-working/
	 */
	public function hookProcessPageViewSendFile(HookEvent $event) {
		$page = $event->arguments[0];
		if(!$this->canUserSee($page)) {
			throw new Wire404Exception($this->_('File not found'));
		}
	}

	/**
	 * Checks if a page is unlocked.
	 * @param  Page    $page
	 * @param  User|boolean $user  if no valid user is passed the current user will be used.
	 * @return boolean
	 */
	public function canUserSee(Page $page, $user = false){
		if(!$user instanceof User || !$user->id) $user = $this->wire('user');
		if(!$this->isUnlocked($page)) return false;
		return true;
	}

	/**
	 * Checks if a page and its parents are unlocked.
	 * @param  Page    $page
	 * @return boolean
	 */
	public function isUnlocked(Page $page){
		if(!$this->isUnlockedSingle($page)) return false;

		foreach($page->parents as $parentPage){
			if(!($parentPage instanceof Page) || !$parentPage->id) continue;
			if(!$this->isUnlockedSingle($parentPage)) return false;
		}

		return true;
	}

	/**
	 * Checks, if a single page is unlocked.
	 * @param  Page    $page
	 * @return boolean
	 */
	public function isUnlockedSingle(Page $page){
		if($page->template->hasField('pageaccess_permissions') && (!$page->template->hasField('pageaccess_permissions_activate') || $page->pageaccess_permissions_activate == true)){
			foreach($page->pageaccess_permissions as $p){
				if(wire('user')->hasPermission($p)){
					return true;
				}
			}
			return false;
		}

		return true;
	}

	/**
	 * Does the page have an activated releasetime-field?
	 * @param  Page    $page
	 * @return boolean
	 */
	public function arePermissionsActivated(Page $page){
		if($page->template->hasField('pageaccess_permissions') && (!$page->template->hasField('pageaccess_permissions_activate') || $page->pageaccess_permissions_activate == true)){
			return true;
		}

		return false;
	}

	/**
	 * Moves the modules fields to the settings-tab
	 * @param  HookEvent $event
	 */
	public function moveFieldToSettings(HookEvent $event) {
		$form = $event->return;

		$settings = $form->find("id=ProcessPageEditSettings")->first();
		if(!$settings) return;

		foreach(self::fieldnames as $fieldname){
			$field = $form->find("name=".$fieldname)->first();
			if(!$field) continue;

			$form->remove($field);
			$settings->append($field);
		}
	}

}