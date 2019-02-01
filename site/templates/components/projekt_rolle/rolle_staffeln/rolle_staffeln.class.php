<?php
namespace ProcessWire;

require_once __DIR__ . '/../rolle_basis.class.php';

/**
 * Stellt die Portraits der Rolle in Staffeln dar.
 */
class RolleStaffeln extends RolleBasis {

	public function __construct($args) {
		parent::__construct($args);

		if(!isset($args['rolle']) || !($args['rolle'] instanceof TwackComponent)){
			throw new ComponentNotInitializedException();
		}
		$this->rolle = $args['rolle'];
	}
}
