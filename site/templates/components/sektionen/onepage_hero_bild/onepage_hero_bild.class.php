<?php namespace ProcessWire;

class OnepageHeroBild extends TwackComponent {
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

		$this->inhalte = '';
		if ($this->page->template->hasField('inhalte')) {
			$this->inhalte = $this->addComponent('Inhalte', ['directory' => '']);
		}

		// Titelbild:
		$this->titelbild = '';
		if ($this->page->titelbild && $this->page->titelbild->url) {
			$this->titelbild = $this->page->titelbild;
		}

		// Hintergrundbild:
		$this->hintergrundbild = '';
		if ($this->page->hintergrundbild && $this->page->hintergrundbild->url) {
			$this->hintergrundbild = $this->page->hintergrundbild;
		}

		$this->addScript('parallax.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/parallax.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
	}
}
