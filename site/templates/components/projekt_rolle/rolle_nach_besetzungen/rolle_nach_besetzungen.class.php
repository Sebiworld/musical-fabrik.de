<?php
namespace ProcessWire;

require_once __DIR__ . '/../rolle_basis.class.php';

/**
 * Stellt die Portraits dar, sortiert nach Besetzungen
 */
class RolleNachBesetzungen extends RolleBasis {

	public function __construct($args) {
		parent::__construct($args);
	}
}
