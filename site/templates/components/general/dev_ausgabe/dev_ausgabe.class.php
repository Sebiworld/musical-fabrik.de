<?php
namespace ProcessWire;

class DevAusgabe extends DevEchoComponent {
	public function __construct($args) {
		parent::__construct($args);
		$this->ausgaben = new WireArray();
	}

	public function devEcho($arguments, $filename = '', $functionname = '', $line = '') {
		$ausgabe = new WireData();

		$ausgabe->arguments = $arguments;
		$ausgabe->filename = $filename;
		$ausgabe->functionname = $functionname;
		$ausgabe->line = $line;

		$this->ausgaben->add($ausgabe);
	}
}
