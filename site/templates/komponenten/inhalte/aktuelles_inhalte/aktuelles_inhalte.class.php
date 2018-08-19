<?php
namespace ProcessWire;

class AktuellesInhalte extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$inhalte = '';
		if (isset($args['typ']) && $args['typ'] == 'kacheln') {
			$inhalte = $this->addComponent('AktuellesKacheln', ['directory' => 'bauteile']);
		} else {
			$inhalte = $this->addComponent('AktuellesCarousel', ['directory' => 'bauteile']);
		}

		// Prüfen, ob auch wirklich ausgebbare Inhalte vorhanden sind (HTML-String nicht leer):
		$this->inhalte_vorhanden = false;
		if (!empty((string) $inhalte)) {
			$this->inhalte_vorhanden = true;
		}

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		$this->title = "Neuigkeiten";
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}
	}
}
