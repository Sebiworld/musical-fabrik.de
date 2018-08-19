<?php
namespace ProcessWire;

class SponsorenBox extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$projektseite = $this->getGlobalParameter('projektseite');
		if (isset($args['projektseite']) && $args['projektseite'] instanceof Page && $args['projektseite']->id) {
			$projektseite = $args['projektseite'];
		}
		if (!($projektseite instanceof Page) || !$projektseite->id) {
			throw new ComponentNotInitialisedException('SponsorenBox', 'Es wurde keine Projektseite gefunden.');
		}

		$this->titel = 'Sponsoren';
		if (isset($args['titel']) && !empty($args['titel'])) {
			$this->titel = str_replace(array("\n", "\r"), '', $args['titel']);
		}

		$nutzefeld = 'foerderer';
		if (isset($args['nutzefeld']) && !empty($args['nutzefeld'])) {
			$nutzefeld = $args['nutzefeld'];
		}

		if (!$projektseite->template->hasField($nutzefeld)) {
			throw new ComponentNotInitialisedException('SponsorenBox', 'Das geforderte Feld wurde auf der Projektseite nicht gefunden.');
		}

		$sponsoren = array();
		foreach ($projektseite->get($nutzefeld)->sort('name') as $sponsor) {
			$sponsoren[] = $sponsor->title;
		}
		$this->sponsoren = $sponsoren;
	}
}
