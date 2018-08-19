<?php
namespace ProcessWire;

class Standardseite extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->konfigurationsseite = $this->getProvider('KonfigurationProvider')->getKonfigurationsseite();
		$this->bildProvider = $this->getProvider('BildProvider');

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
			$zeitstempel_von = $this->page->getUnformatted('zeitpunkt_von');
			$this->datum = date('d.m.Y', $zeitstempel_von);

			if ($this->page->template->hasField('zeitpunkt_bis')) {
				// Zeitpunkt-bis-Feld existiert: Wahrscheinlich Veranstaltung, also Uhrzeit mit ausgeben
				$this->datum .= date('d.m.Y, H:m', $zeitstempel_von) . ' Uhr';
				if (!empty($this->page->zeitpunkt_bis)) {
					if (date('d.m.Y', $zeitstempel_von) == date('d.m.Y', $this->page->getUnformatted('zeitpunkt_bis'))) {
						// Gleicher Tag, nur Uhrzeit hinzufügen
						$this->datum = substr($this->datum, 0, -4) . ' bis ' . date('H:m', $this->page->getUnformatted('zeitpunkt_bis')) . ' Uhr';
					} else {
						$this->datum .= ' bis ' . date('d.m.Y, H:m', $this->page->getUnformatted('zeitpunkt_bis')) . ' Uhr';
					}
				}
			}
		}

		$this->schlagwoerter = $this->addComponent('SchlagwoerterFeld', ['directory' => 'bauteile', 'name' => 'schlagwoerter']);

		if ($this->page->template->hasField('inhalte')) {
			$this->addComponent('Inhalte', ['directory' => '']);
		}

		$this->addStyle(wire('config')->urls->templates . 'assets/css/standardseite.min.css', true, true);
	}
}
