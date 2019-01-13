<?php
namespace ProcessWire;

class DateienBox extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if (!isset($args['nutzefeld']) || empty($args['nutzefeld'])) {
			throw new ComponentNotInitializedException('DateienBox', 'Bitte übergeben Sie den Namen des Datei-Feldes, aus dem die Download-Dateien gezogen werden sollen.');
		}

		$this->dateien = $this->page->get($args['nutzefeld']);

		if (isset($args['titel']) && !empty($args['titel'])) {
			$this->titel = str_replace(array("\n", "\r"), '', $args['titel']);
		}
	}
}
