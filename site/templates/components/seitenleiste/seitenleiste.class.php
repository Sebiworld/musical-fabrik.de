<?php
namespace ProcessWire;

class Seitenleiste extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if (isset($args['klassen']) && is_array($args['klassen'])) {
			$this->klassen = $args['klassen'];
		}

		// $this->addStyle(wire('config')->urls->templates . 'assets/css/seitenleiste.min.css', true, true);
	}
}
