<?php
namespace ProcessWire;

class KonfigurationService extends TwackComponent {

	protected $konfigurationsseite;

	public function __construct($args) {
		parent::__construct($args);
		$this->konfigurationsseite = wire('pages')->get('template.name=konfiguration');
	}

	public function getKonfigurationsseite() {
		return $this->konfigurationsseite;
	}
}
