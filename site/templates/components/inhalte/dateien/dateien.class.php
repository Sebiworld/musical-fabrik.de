<?php
namespace ProcessWire;

class Dateien extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->dateien = array();
		if (isset($args['dateien'])) {
			$this->dateien = $args['dateien'];
		} elseif ($this->page->template->hasField('dateien') && !empty($this->page->dateien)) {
			$this->dateien = $this->page->dateien;
		}

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}

		if (isset($args['beschreibung'])) {
			$this->beschreibung = $args['beschreibung'];
		} elseif ($this->page->template->hasField('text') && !empty($this->page->text)) {
			$this->beschreibung = $this->page->text;
		}

		$this->addScript(wire('config')->urls->templates . 'assets/js/mediaplayer.min.js', true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/mediaplayer.legacy.min.js', true);
		// $this->addStyle(wire('config')->urls->templates . 'assets/css/inhalte-dateien.min.css', true, true);
	}
}
