<?php
namespace ProcessWire;

class Diagramm extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->addScript(wire('config')->urls->templates . 'assets/js/diagramm.min.js', true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/diagramm.legacy.min.js', true);

		// Der Titel kann per $args oder per Feld "title" gesetzt werden:
		if (isset($args['titel'])) {
			$this->titel = $args['titel'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->titel = $this->page->title;
		}

		if (!$this->page->template->hasField('diagramm') || $this->page->diagramm->count < 1) {
			throw new ComponentNotInitializedException("Diagramm", "Es wurde kein Diagramm gefunden.");
		}

		$this->diagramm = $this->page->diagramm->first();
		// $this->addStyle(wire('config')->urls->templates . 'assets/css/inhalte-diagramm.min.css', true, true);
	}

	/**
	 * Liefert Informationen zu einem bestimmten Diagramm per Ajax
	 * @return array
	 */
	public function getAjax() {
		$ausgabe = array(
			'typ' => $this->diagramm->type
		);

		if (isset($this->titel) && !empty($this->titel) && !$this->page->titel_verstecken) {
			$ausgabe['titel'] = $this->titel;
		}

		if ($this->diagramm->type === 'bar') {
			$ausgabe['werte'] = $this->diagramm->diagramm_werte->explode(['wert', 'label', 'farbe']);
			$ausgabe['labels'] = $this->diagramm->diagramm_labels->explode(['wert_minimum', 'wert_maximum', 'label']);
		} elseif ($this->diagramm->type === 'doughnut') {
			$ausgabe['werte'] = $this->diagramm->diagramm_werte->explode(['wert', 'label', 'farbe']);
		}

		return $ausgabe;
	}
}
