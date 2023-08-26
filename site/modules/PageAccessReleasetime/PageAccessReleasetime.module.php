<?php
namespace ProcessWire;

class PageAccessReleasetime extends WireData implements Module, ConfigurableModule {

	const module_tags = 'Releasetime';
	const fieldnames = array('releasetime_start_activate', 'releasetime_start', 'releasetime_end_activate', 'releasetime_end');
	const permissionname = 'page-view-not-released';

	// Add this selector to your query, to filter unreleased pages:
	const selector = "or1=(releasetime_start_activate=0), or1=(releasetime_start=''), or1=(releasetime_start<=now), or2=(releasetime_end_activate=0), or2=(releasetime_end=''), or2=(releasetime_end>=now)";

	public static function getModuleInfo() {
		return array(
			'title' => __('Page Access Releasetime'),
			'author' => 'Sebastian Schendel',
			'version' => '1.0.4',
			'summary' => __('Enables you to set a start- and end-time for the release of pages. Prevents unreleased pages from being displayed.'),
			'singular' => true,
			'autoload' => true,
			'icon' => 'hourglass-half',
			'requires' => array('PHP>=5.5.0', 'ProcessWire>=3.0.0'),
			'href' => 'https://processwire.com/talk/topic/20852-module-page-access-releasetime/'
		);
	}

	public function ___install(){
		$flags = Field::flagSystem + Field::flagAccessAPI + Field::flagAutojoin;

		$field = new Field();
		$field->type = $this->modules->get("FieldtypeCheckbox");
		$field->name = 'releasetime_start_activate';
		$field->label = $this->_('Activate Releasetime from?');
		$field->tags = self::module_tags;
		$field->flags = $flags;
		$field->save();

		$field = new Field();
		$field->type = $this->modules->get("FieldtypeDatetime");
		$field->name = 'releasetime_start';
		$field->label = $this->_('Release from:');
		$field->tags = self::module_tags;
		$field->flags = $flags;
		$field->timeInputSelect = true;
		$field->timeInputFormat = 'H:i:s';
		$field->datepicker = InputfieldDatetime::datepickerFocus;
		$field->defaultToday = true;
		$field->showIf = 'releasetime_start_activate=1';
		$field->requiredIf = 'releasetime_start_activate=1';
		$field->save();

		$field = new Field();
		$field->type = $this->modules->get("FieldtypeCheckbox");
		$field->name = 'releasetime_end_activate';
		$field->label = $this->_('Activate Releasetime to?');
		$field->tags = self::module_tags;
		$field->flags = $flags;
		$field->save();

		$field = new Field();
		$field->type = $this->modules->get("FieldtypeDatetime");
		$field->name = 'releasetime_end';
		$field->label = $this->_('Release to:');
		$field->tags = self::module_tags;
		$field->flags = $flags;
		$field->timeInputSelect = true;
		$field->timeInputFormat = 'H:i:s';
		$field->datepicker = InputfieldDatetime::datepickerFocus;
		$field->showIf = 'releasetime_end_activate=1';
		$field->requiredIf = 'releasetime_end_activate=1';
		$field->save();

		$permission = $this->wire('permissions')->add(self::permissionname);
		$permission->title = $this->_('Can see pages that are not yet released.');
		$permission->save();
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

		// remove permission:
		$permission = wire('permissions')->get(self::permissionname);
		if($permission instanceof Permission && $permission->id){
			$this->wire('permissions')->delete($permission);
		}

	}

	protected static $defaults = array(
		'autoAdd' => 0,
		'templates' => []
	);

	public static function getModuleConfigInputfields(array $data) {
		$form  = new InputfieldWrapper();
		$moduleconfig = wire('modules')->getModuleConfigData('PageAccessReleasetime');
		$data = array_merge(self::$defaults, $data);

		$f = new InputfieldCheckbox();
		$f->attr('id+name', 'autoAdd');
		$f->label =  __('Mode');
		$f->label2 =  __('Add to all templates automatically');
		$f->description =  __('Enabling this feature will add the fields to every page automatically.');
		$f->value = $data['autoAdd'];
		$checked = isset($data['autoAdd']) && $data['autoAdd'] == '' ?  '' : 'checked';
		$f->attr('checked', $checked);
		$form->add($f);

		$f = new InputfieldAsmSelect();
		$f->attr('id+name', 'templates');
		$f->label =  __('Templates');
		$f->description = __("Select all templates that should get the releasetime fields.");
		$f->showIf = 'autoAdd=0';

		// Dynamically check which templates have all releasetime-fields and check them afterwards:
		$templatesWithFields = array();

		foreach(wire('templates') as $template) {
			// Exclude system templates:
			if($template->flags & Template::flagSystem) continue;
		    $f->addOption($template->id, $template->getLabel() . ' (' . $template->name . ')');

		    // Check, if the template already has all releasetime-fields:
		    $allFieldsExistFlag = true;
		    foreach(self::fieldnames as $fieldname){
		    	if(!$template->hasField($fieldname)){
		    		$allFieldsExistFlag = false;
		    		break;
		    	}
		    }

		    if($allFieldsExistFlag){
		    	$templatesWithFields[] = $template->id;
		    }
		}

		$data['templates'] = $templatesWithFields;
		$f->attr('value', $data['templates']);

		$form->add($f);

		return $form;
	}

	public function init() {
		$this->addHookAfter("Modules::saveConfig", $this, "hookModulesSaveConfig");

		// Move releasetime-fields to settings-tab
		$this->addHookAfter("ProcessPageEdit::buildForm", $this, "moveFieldToSettings");

		// Prevent unreleased pagse from being viewed
		$this->addHook('Page::viewable', $this, 'hookPageViewable');

		// Unreleased pages cannot be listed:
		$this->addHook('Page::listable', $this, 'hookPageListable');

		// Manage access to files ($config->pagefileSecure has to be true)
		$this->addHookAfter('Page::isPublic', $this, 'hookPageIsPublic');
		$this->addHookBefore('ProcessPageView::sendFile', $this, 'hookProcessPageViewSendFile');

		// TODO: Can we manipulate $pages->find() to exclude unreleased pages?
		// $this->addHookBefore('Pages::find', $this, 'beforePagesFind');
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
			// If the page would be listable, additionally check Releasetime and User-Permission
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
		if($event->return && $this->isReleaseTimeSet($page)) {
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
	 * Checks wether a page is unlocked or the current user has the permission "page-view-not-released" which enables them to see unreleased pages.
	 * @param  Page    $page
	 * @param  User|boolean $user  if no valid user is passed the current user will be used.
	 * @return boolean
	 */
	public function canUserSee(Page $page, $user = false){
		if(!$user instanceof User || !$user->id) $user = $this->wire('user');
		if($user->isSuperuser() || $user->hasPermission(self::permissionname)) return true;

		if(!$this->isReleased($page)) return false;

		return true;
	}

	/**
	 * Checks if a page and its parents are released yet.
	 * @param  Page    $page
	 * @return boolean
	 */
	public function isReleased(Page $page){
		if(!$this->isReleasedSingle($page)) return false;

		foreach($page->parents as $parentPage){
			if(!($parentPage instanceof Page) || !$parentPage->id) continue;
			if(!$this->isReleasedSingle($parentPage)) return false;
		}

		return true;
	}

	/**
	 * Checks, if a single page is released.
	 * @param  Page    $page
	 * @return boolean
	 */
	public function isReleasedSingle(Page $page){
		if($page->template->hasField('releasetime_start') && (!$page->template->hasField('releasetime_start_activate') || $page->releasetime_start_activate == true) && $page->releasetime_start > time()){
			return false;
		}else if($page->template->hasField('releasetime_end') && (!$page->template->hasField('releasetime_end_activate') || $page->releasetime_end_activate == true) && $page->releasetime_end < time()){
			return false;
		}

		return true;
	}

	/**
	 * Does the page have an activated releasetime-field?
	 * @param  Page    $page
	 * @return boolean
	 */
	public function isReleaseTimeSet(Page $page){
		if($page->template->hasField('releasetime_start') && (!$page->template->hasField('releasetime_start_activate') || $page->releasetime_start_activate == true)){
			return true;
		}

		if($page->template->hasField('releasetime_end') && (!$page->template->hasField('releasetime_end_activate') || $page->releasetime_end_activate == true)){
			return true;
		}

		return false;
	}

	/**
	 * Adds releasetime-fields to the wanted templates after module's save
	 * @param  HookEvent $event
	 */
	public function hookModulesSaveConfig(HookEvent $event){
		// Get the object the event occurred on, if needed
		$modules = $event->object;

		$data = $event->arguments(1);

		// If auto-add is activated: Make fields global
		if(isset($data['autoAdd']) && $data['autoAdd']){
			foreach(self::fieldnames as $fieldname){
				$field = $this->wire('fields')->get($fieldname);
				if(!($field instanceof Field) || $field->name != $fieldname){
					continue;
				}

				if($field->hasFlag(Field::flagGlobal)){
					// Field is already global
					continue;
				}

				$field->addFlag(Field::flagGlobal);
				$field->save();
			}
			return;
		}

		// auto-add is not activated. The fields should not be global an will be added manually.
		foreach(self::fieldnames as $fieldname){
			$field = $this->wire('fields')->get($fieldname);
			if(!($field instanceof Field) || $field->name != $fieldname){
				continue;
			}

			if(!$field->hasFlag(Field::flagGlobal)){
				// Field is not global
				continue;
			}

			$field->removeFlag(Field::flagGlobal);
			$field->save();
		}

		$savedTemplates = array();
		if(isset($data['templates']) && is_array($data['templates'])){
			$savedTemplates = $data['templates'];
		}

		foreach(wire('templates') as $template){
			if(array_search($template->id, $savedTemplates) !== false){

				// Fields should be added
				foreach(self::fieldnames as $fieldname){
					if($template->hasField($fieldname)){
						// Field is already there
						continue;
					}

					$template->fieldgroup->add($fieldname);
					$template->fieldgroup->save();
				}
				continue;
			}

			// Fields should be removed
			foreach(self::fieldnames as $fieldname){
				if(!$template->hasField($fieldname)){
					// Field was not added
					continue;
				}

				$template->fieldgroup->remove($fieldname);
				$template->fieldgroup->save();
			}
		}
	}

	/**
	 * Moves the releasetime-fields to the settings-tab
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