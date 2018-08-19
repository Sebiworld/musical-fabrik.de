<?php
namespace ProcessWire;

class BildModal extends BasisModal {
	public function __construct($args) {
		parent::__construct($args);

		if (isset($args['bilder'])) {
			$this->bilder = $args['bilder'];
		}

		$this->einzelbild = isset($args['einzelbild']) && !!$args['einzelbild'];
	}
}
