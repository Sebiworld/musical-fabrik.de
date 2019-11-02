<?php
namespace ProcessWire;

class FormsComponent extends TwackComponent {
	public function __construct($args) {
		require_once(__DIR__ . "/form_exception.class.php");
		parent::__construct($args);
	}
}
