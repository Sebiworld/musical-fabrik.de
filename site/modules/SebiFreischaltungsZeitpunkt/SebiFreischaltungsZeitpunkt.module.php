<?php namespace ProcessWire;

class SebiFreischaltungsZeitpunkt extends WireData implements Module {

	const module_tags = 'FreischaltungsZeitpunkt';
	protected $feldernamen = array('freischaltungszeitpunkt_zeitpunkt_ab_aktivieren', 'freischaltungszeitpunkt_zeitpunkt_ab', 'freischaltungszeitpunkt_zeitpunkt_bis_aktivieren', 'freischaltungszeitpunkt_zeitpunkt_bis');

	public static function getModuleInfo() {
		return array(
			'title' => 'Freischaltungs-Zeitpunkt Modul',
			'version' => '1.0.0',
			'summary' => 'Ermöglicht das Setzen eines Start- und Endzeitpunktes für die Freischaltung von Seiten. Verhindert, dass nicht freigeschaltete Seiten angezeigt werden.',
			'singular' => true,
			'autoload' => true,
			'icon' => 'hourglass-half',
			'requires' => array('PHP>=5.5.0', 'ProcessWire>=3.0.0'),
		);
	}

	public function ___install(){
		$flags = Field::flagGlobal + Field::flagSystem + Field::flagPermanent + Field::flagAccessAPI + Field::flagAutojoin;

		$field = new Field();
		$field->type = $this->modules->get("FieldtypeCheckbox");
		$field->name = 'freischaltungszeitpunkt_zeitpunkt_ab_aktivieren';
		$field->label = 'Veröffentlichen-ab aktivieren?';
		// $field->tags = self::module_tags;
		$field->flags = $flags;
		$field->save();

		$field = new Field();
		$field->type = $this->modules->get("FieldtypeDatetime");
		$field->name = 'freischaltungszeitpunkt_zeitpunkt_ab';
		$field->label = 'Veröffentlichen ab';
		// $field->tags = self::module_tags;
		$field->flags = $flags;
		$field->dateInputFormat = 'd.m.Y';
		$field->timeInputFormat = 'H:i:s';
		$field->datepicker = true;
		$field->defaultToday = true;
		$field->showIf = 'freischaltungszeitpunkt_zeitpunkt_ab_aktivieren=1';
		$field->save();

		$field = new Field();
		$field->type = $this->modules->get("FieldtypeCheckbox");
		$field->name = 'freischaltungszeitpunkt_zeitpunkt_bis_aktivieren';
		$field->label = 'Veröffentlichen-bis aktivieren?';
		// $field->tags = self::module_tags;
		$field->flags = $flags;
		$field->save();

		$field = new Field();
		$field->type = $this->modules->get("FieldtypeDatetime");
		$field->name = 'freischaltungszeitpunkt_zeitpunkt_bis';
		$field->label = 'Veröffentlichen bis';
		// $field->tags = self::module_tags;
		$field->flags = $flags;
		$field->dateInputFormat = 'd.m.Y';
		$field->timeInputFormat = 'H:i:s';
		$field->datepicker = true;
		$field->showIf = 'freischaltungszeitpunkt_zeitpunkt_bis_aktivieren=1';
		$field->save();

		$permission = wire('permissions')->add('page-view-nicht-freigeschaltet');
		$permission->title = 'Kann nicht freigeschaltete Seiten sehen.';
		$permission->save();
	}

	public function ___uninstall(){
		foreach($this->feldernamen as $feldname){
			$feld = wire('fields')->get($feldname);
			if(!($feld instanceof Field) || $feld->name != $feldname) continue;
			$feld->flags = Field::flagSystemOverride;
			$feld->flags = 0;
			$feld->save();

			foreach(wire('templates') as $template){
				if(!$template->hasField($feldname)) continue;
				$template->fieldgroup->remove($feld);
				$template->fieldgroup->save();
			}

			wire('fields')->delete($feld);
		}
	}

	public function init() {
		$this->addHook('Page::viewable', $this, 'viewable');
		// $this->addHookBefore('Pages::find', $this, 'beforePagesFind');
		$this->addHookAfter("ProcessPageEdit::buildForm", $this, "moveFieldToSettings");
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
	public function viewable($event) {
		$seite = $event->object;
		$viewable = $event->return;

		if($viewable){
			// Hier können spezielle Anweisungen geprüft werden, durch die eine Seite nicht sichtbar sein soll.
			$viewable = $this->canUserSee($seite);
		}
		$event->return = $viewable;
	}

	public function canUserSee(Page $seite, $user = false){
		if(!$user instanceof User || !$user->id) $user = wire('user');
		if($user->isSuperuser() || $user->hasPermission('page-view-nicht-freigeschaltet')) return true;
		// return $this->isFreigeschaltet($seite);
		if(!$this->isFreigeschaltet($seite)) return false;

		foreach($seite->parents as $elternSeite){
			if(!($elternSeite instanceof Page) || !$elternSeite->id) continue;
			if(!$this->isFreigeschaltet($elternSeite)) return false;
		}
		return true;
	}

	/**
	 * Prüft, ob die übergebene Seite freigeschaltet ist.
	 * @param  Page    $seite
	 * @return boolean
	 */
	public function isFreigeschaltet(Page $seite){
		// Hier können spezielle Anweisungen geprüft werden, durch die eine Seite nicht sichtbar sein soll.
		if($seite->template->hasField('freischaltungszeitpunkt_zeitpunkt_ab') &&
			(!$seite->template->hasField('freischaltungszeitpunkt_zeitpunkt_ab_aktivieren') || $seite->freischaltungszeitpunkt_zeitpunkt_ab_aktivieren == true)){
			if($seite->freischaltungszeitpunkt_zeitpunkt_ab > time()){
				return false;
			}
		}else if($seite->template->hasField('freischaltungszeitpunkt_zeitpunkt_bis') &&
			(!$seite->template->hasField('freischaltungszeitpunkt_zeitpunkt_bis_aktivieren') || $seite->freischaltungszeitpunkt_zeitpunkt_bis_aktivieren == true)){
			if($seite->freischaltungszeitpunkt_zeitpunkt_bis < time()){
				return false;
			}
		}

		return true;
	}

	// TODO
	// public function beforePagesFind(HookEvent $event) {
 // 		// Get the object the event occurred on, if needed
	// 	$pages = $event->object;

 //  		// Get values of arguments sent to hook (and optionally modify them)
	// 	$selector = $event->arguments(0);
	// 	$options = $event->arguments(1);

	// 	// if(!emtpy($selector)) $selector .= ' ,';
	// 	// $selector .= ''

 //  		// Populate back arguments (if you have modified them)
	// 	$event->arguments(0, $selector);
	// 	$event->arguments(1, $options);
	// }

	public function moveFieldToSettings(HookEvent $event) {
		$form = $event->return;

		foreach($this->feldernamen as $feldname){
			$feld = $form->find("name=".$feldname)->first();

			if($feld) {
				$settings = $form->find("id=ProcessPageEditSettings")->first();

				if($settings) {
					$form->remove($feld);
					$settings->append($feld);
					// In the alternative, insert before or after the found field:
					// $form->insertBefore($feld, $settings);
				}
			}
		}
	}

}