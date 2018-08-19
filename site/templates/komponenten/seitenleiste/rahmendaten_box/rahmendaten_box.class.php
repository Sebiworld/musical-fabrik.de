<?php
namespace ProcessWire;

class RahmendatenBox extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->daten = new WireArray();

		$projektseite = $this->getGlobalParameter('projektseite');
		if (isset($args['projektseite']) && $args['projektseite'] instanceof Page && $args['projektseite']->id) {
			$projektseite = $args['projektseite'];
		}
		if (!($projektseite instanceof Page) || !$projektseite->id) {
			throw new ComponentNotInitialisedException('RahmendatenBox', 'Es wurde keine Projektseite gefunden.');
		}

		$this->titel = 'Projektdaten';
		if (isset($args['titel']) && !empty($args['titel'])) {
			$this->titel = str_replace(array("\n", "\r"), '', $args['titel']);
		}

		$rollenProvider = $this->getProvider('RollenProvider');

		// Anzahl der Mitwirkenden:
		$rollenSeite = $projektseite->get('template.name=rollen_container');
		if ($rollenSeite->id) {
			// Gesamtanzahl:
			$this->gesamtanzahl = 10 * floor($rollenProvider->getMitwirkendenAnzahl($rollenSeite) / 10);
			if ($this->gesamtanzahl > 0) {
				$rollenText = 'Mehr als ' . $this->gesamtanzahl . ' Mitwirkende!';
				$this->addDatensatz('', $rollenText);

				foreach ($rollenSeite->children('template.name=rolle') as $rolle) {
					$this->addDatensatz('<i class="icon ion-ios-arrow-round-forward"></i>&nbsp; '.$rolle->title, "", $rolle->url, $rolle->title, 1);
				}
			}
		}
	}

	public function addDatensatz($name, $wert = '', $link = '', $linktitle = '', $ebene = 0) {
		$datensatz = new WireData();
		$datensatz->label = '';

		if (!empty($name)) {
			$datensatz->name = $name;
			$datensatz->label = $name;
		}
		if (!empty($wert)) {
			if (!empty($datensatz->label)) {
				$datensatz->label .= ': ';
			}
			$datensatz->wert = $wert;
			$datensatz->label .= $wert;
		}
		if (!empty($link)) {
			$datensatz->link = $link;
		}
		if (!empty($linktitle)) {
			$datensatz->linktitle = $linktitle;
		}
		$datensatz->ebene = $ebene;

		$this->daten->add($datensatz);
	}
}
