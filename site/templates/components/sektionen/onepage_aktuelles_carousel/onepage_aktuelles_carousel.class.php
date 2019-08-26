<?php
namespace ProcessWire;

class OnepageAktuellesCarousel extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		// Die ID der Onepage-Sektion ermitteln:
		$this->sektionID = '';
		if ((string) $this->page->sektion_name) {
			$this->sektionID = (string) $this->page->sektion_name;
		}

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		$this->titel = 'Neuigkeiten';
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}

		$this->addComponent('AktuellesCarousel', ['directory' => 'bauteile', 'name' => 'carousel']);

		if ($this->page->template->hasField('inhalte')) {
			$this->addComponent('Inhalte', ['directory' => '']);
		}
	}
}
