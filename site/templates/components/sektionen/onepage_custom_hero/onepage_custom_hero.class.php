<?php namespace ProcessWire;

class OnepageCustomHero extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->bildService = $this->getService('BildService');

		// Die ID der Onepage-Sektion ermitteln:
		$this->sektionID = '';
		if ((string) $this->page->sektion_name) {
			$this->sektionID = (string) $this->page->sektion_name;
		}

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}

		// $this->addStyle(wire('config')->urls->templates . 'assets/css/custom-hero.min.css', true, true);
		// $this->addStyle(wire('config')->urls->templates . 'assets/css/sektion-custom-hero.min.css', true, true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/custom-hero.min.js', true, true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/custom-hero.legacy.min.js', true, true);
	}
}
