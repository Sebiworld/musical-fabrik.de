<?php
namespace ProcessWire;

class Formular extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if (!$this->page->template->hasField('formular')) {
			throw new ComponentNotInitializedException('OnepageFormular', 'Am Onepage-Formular wurde kein Feld für die Containerseite hinterlegt.');
		}

		$containerSeite = $this->page->get('formular');
		if (!($containerSeite instanceof Page) || !$containerSeite->id) {
			throw new ComponentNotInitializedException('OnepageFormular', 'Im Onepage-Formular wurde keine valide Containerseite angegeben.');
		}

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}

		$platzhaltertexte = array();
		$formulare = $this->getGlobalComponent('formulare');
		$this->formular = $formulare->addComponent('TemplateFormular', [
			'containerSeite' => $containerSeite,
			'platzhaltertexte' => $platzhaltertexte,
			'page' => $this->page
			]);
	}
}
