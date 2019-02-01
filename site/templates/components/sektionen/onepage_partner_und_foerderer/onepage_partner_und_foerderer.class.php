<?php
namespace ProcessWire;

class OnepagePartnerUndFoerderer extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

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

		if ($this->page->template->hasField('inhalte')) {
			$this->inhalte = $this->addComponent('Inhalte', ['directory' => '', 'page' => $this->page]);
		}

		$this->partner = new WireArray();
		if ($this->page->template->hasField('partner')) {
			$this->partner = $this->page->partner->sort('random');
		}

		$this->foerderer = new WireArray();
		if ($this->page->template->hasField('foerderer')) {
			$this->foerderer = $this->page->foerderer->sort('random');
		}

		$this->addStyle(wire('config')->urls->templates . 'assets/css/sektion-partner-foerderer.min.css', true, true);
	}
}
