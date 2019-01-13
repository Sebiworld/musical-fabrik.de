<?php
namespace ProcessWire;

class SeitenleistenKarte extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if (!isset($args['nutzefeld']) || empty($args['nutzefeld'])) {
			throw new ComponentNotInitializedException('SeitenleistenKarte', 'Bitte übergeben Sie den Namen des Kartenfeldes, das genutzt werden soll.');
		}

		$this->karte = $this->page->get($args['nutzefeld']);

		$mapModule = wire('modules')->get('FieldtypeMapMarker');
		if (!empty($this->karte->address)) {
			$this->addScript('https://maps.googleapis.com/maps/api/js?key='.$mapModule->get('googleApiKey').'&callback=initMap', true);
		}

		$this->adresse = '';
		if (isset($args['adresse']) && !empty($args['adresse'])) {
			$this->adresse = str_replace(array("\n", "\r"), '', $args['adresse']);
		}
	}
}
