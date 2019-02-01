<?php
namespace ProcessWire;

class Audiodateien extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->audiodateien = array();
		if (isset($args['audiodateien'])) {
			$this->audiodateien = $args['audiodateien'];
		} elseif ($this->page->template->hasField('audiodateien') && !empty($this->page->audiodateien)) {
			$this->audiodateien = $this->page->audiodateien;
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
	}
}
