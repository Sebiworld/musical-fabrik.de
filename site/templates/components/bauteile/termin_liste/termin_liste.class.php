<?php
namespace ProcessWire;

class TerminListe extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
		$this->termineService = $this->getService('TermineService');
	}

	public function getAjax() {
		return $this->termineService->getAjax();
	}
}
