<?php
namespace ProcessWire;

require_once __DIR__ . '/../rolle_basis.class.php';

/**
 * Stellt die Portraits der Rolle als Block dar.
 */
class RolleAlsBlock extends RolleBasis {

	public function __construct($args) {
		parent::__construct($args);
	}
}
