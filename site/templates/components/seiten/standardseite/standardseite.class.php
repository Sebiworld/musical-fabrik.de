<?php
namespace ProcessWire;

class Standardseite extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->konfigurationsseite = $this->getService('KonfigurationService')->getKonfigurationsseite();
		$this->bildService = $this->getService('BildService');

		// Der globalen Komponente 'inhalt' können Inhalte hinzugefügt werden:
		$this->twack->makeComponentGlobal($this, 'inhalt');

		$this->einzelbildModalID = $this->getGlobalParameter('einzelbildModalID');

		$this->titel = $this->page->title;
		if ($this->page->template->hasField('ueberschrift') && !empty((string) $this->page->ueberschrift)) {
			$this->titel = $this->page->ueberschrift;
		}

		if ($this->page->template->hasField('einleitung') && !empty((string) $this->page->einleitung)) {
			$this->einleitung = $this->page->einleitung;
		}

		if ($this->page->template->hasField('kommentare') && $this->page->kommentare) {
			$this->kommentare = $this->addComponent('Kommentare', ['name' => 'kommentare', 'directory' => 'bauteile']);
		}

		if ($this->page->template->hasField('titelbild') && $this->page->titelbild) {
			$this->titelbild = $this->page->titelbild;
		}

		if ($this->page->template->hasField('autoren') && $this->page->autoren instanceof PageArray) {
			$autoren = array();
			foreach ($this->page->autoren as $autor) {
				$autoren[] = $autor->vorname . ' ' . $autor->nachname;
			}
			if (count($autoren) > 0) {
				$this->autoren = $autoren;
			}
		}

		if ($this->page->template->hasField('zeitpunkt_von') && !empty($this->page->zeitpunkt_von)) {
			$this->datum_unformatted = $this->page->getUnformatted('zeitpunkt_von');
			$this->datum = date('d.m.Y', $this->datum_unformatted);


			if ($this->page->template->hasField('zeitpunkt_bis')) {
				// Zeitpunkt-bis-Feld existiert: Wahrscheinlich Veranstaltung, also Uhrzeit mit ausgeben
				$this->datum .= date('d.m.Y, H:m', $this->datum_unformatted) . ' Uhr';
				if (!empty($this->page->zeitpunkt_bis)) {
					$this->datum_bis_unformatted = $this->page->getUnformatted('zeitpunkt_bis');
					if (date('d.m.Y', $this->datum_unformatted) == date('d.m.Y', $this->page->getUnformatted('zeitpunkt_bis'))) {
						// Gleicher Tag, nur Uhrzeit hinzufügen
						$this->datum = substr($this->datum, 0, -4) . ' bis ' . date('H:m', $this->datum_bis_unformatted) . ' Uhr';
					} else {
						$this->datum .= ' bis ' . date('d.m.Y, H:m', $this->datum_bis_unformatted) . ' Uhr';
					}
				}
			}
		}

		$this->schlagwoerter = $this->addComponent('SchlagwoerterFeld', ['directory' => 'bauteile', 'name' => 'schlagwoerter']);

		if ($this->page->template->hasField('inhalte')) {
			$this->inhalte = $this->addComponent('Inhalte', ['directory' => '']);
		}

		$this->addStyle('standardseite.css', array(
            'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true,
			'inline' => true
        ));
	}

	public function getAjax() {
		$output = array(
			'title' => $this->titel
		);

		if (!empty($this->datum_unformatted)) {
			$output['datum'] = $this->datum_unformatted;
		}

		if (!empty($this->datum_bis_unformatted)) {
			$output['datum_bis'] = $this->datum_bis_unformatted;
		}

		if (!empty($this->einleitung)) {
			$output['einleitung'] = $this->einleitung;
		}

		if (!empty($this->titelbild)) {
			$output['titelbild'] = $this->getAjaxOf($this->titelbild);
		}

		if (!empty($this->autoren)) {
			$output['autoren'] = $this->autoren;
		}

		if ($this->schlagwoerter && $this->schlagwoerter instanceof TwackComponent) {
			$schlagwortAjax = $this->schlagwoerter->getAjax();
			if(!empty($schlagwortAjax)){
				$output['schlagwoerter'] = $schlagwortAjax;
			}
		}

		if ($this->inhalte && $this->inhalte instanceof TwackComponent) {
			$output['inhalte'] = $this->inhalte->getAjax();
		}

		// Die Komponente ist unter dem globalen Namen "inhalt" registriert. Aus den Templatefiles werden teilweise manuell Komponenten dazu hinugefügt.
		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax();
				if(empty($ajax)) continue;
				$output = array_merge($output, $ajax);
			}
		}

		return $output;
	}
}
