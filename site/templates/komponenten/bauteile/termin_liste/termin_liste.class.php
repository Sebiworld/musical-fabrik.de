<?php
namespace ProcessWire;

class TerminListe extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
		$this->termineProvider = $this->getProvider('TermineProvider');
	}

	public function getAjax() {
		return $this->termineProvider->getAjax();
	}
}
