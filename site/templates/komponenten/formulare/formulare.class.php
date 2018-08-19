<?php
namespace ProcessWire;

class Formulare extends TwackComponent {
	public function __construct($args) {
		require_once(__DIR__ . "/formular_exception.class.php");
		parent::__construct($args);
	}
}
