<?php
namespace ProcessWire;

class DevOutput extends DevEchoComponent {
	public function __construct($args) {
		parent::__construct($args);
		$this->outputs = new WireArray();
	}

	public function devEcho($arguments, $filename = '', $functionname = '', $line = '') {
		$output = new WireData();

		$output->arguments = $arguments;
		$output->filename = $filename;
		$output->functionname = $functionname;
		$output->line = $line;

		$this->outputs->add($output);
	}
}
