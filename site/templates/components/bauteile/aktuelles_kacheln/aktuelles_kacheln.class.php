<?php

namespace ProcessWire;

class AktuellesKacheln extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $filtereinstellungen = array(
            'zeichenLimit' => 150
        );

        // Ist ein Schlagwort-Filter gesetzt?
        if (wire('input')->get('schlagwoerter')) {
            $filtereinstellungen['schlagwoerter'] = wire('input')->get('schlagwoerter');
        }

        // Ist etwas bei der Freitextsuche eingetragen?
        if (wire('input')->get('freitextsuche')) {
            $filtereinstellungen['freitextsuche'] = wire('input')->get('freitextsuche');
		}

        if ($this->page->closest('template^=projekt') instanceof NullPage) {
            $this->addComponent('Filtereinstellungen', [
				'directory' => 'bauteile', 
				'name' => 'filtereinstellungen', 
				'filtereinstellungen' => $filtereinstellungen
			]);
        }

        $this->aktuellesService    = $this->getService('AktuellesService');
        $aktuelles                 = $this->aktuellesService->getBeitraege($filtereinstellungen);
        $this->hatMehr             = $aktuelles->hatMehr;
        $this->letztesElementIndex = $aktuelles->letztesElementIndex;
        $this->gesamtAnzahl        = $aktuelles->gesamtAnzahl;
        $beitragsSeiten            = $aktuelles->beitraege;

        foreach ($beitragsSeiten as $seite) {
            $this->addComponent('BeitragCard', ['directory' => 'bauteile', 'page' => $seite]);
        }

        $this->aktuellesSeite = $this->aktuellesService->getAktuellesSeite();
        // $this->addStyle(wire('config')->urls->templates . 'assets/css/aktuelles-kacheln.min.css', true);
        $this->addScript(wire('config')->urls->templates . 'assets/js/ajaxmasonry.min.js', true);
        $this->addScript(wire('config')->urls->templates . 'assets/js/ajaxmasonry.legacy.min.js', true);
    }

    public function getAjax() {
        return $this->aktuellesService->getAjax();
    }
}
