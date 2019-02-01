<?php
namespace ProcessWire;

/**
 * Basis-Funktionen, die jedes Modal braucht. Muss von Modal-Komponenten per "extends BasicModal" eingebunden werden.
 */
abstract class BasisModal extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if (!isset($args['id']) || !is_string($args['id'])) {
			throw new ComponentNotInitializedException('Modal', 'Es wurde keine gültige ID an das Modal übergeben.');
		}
		$this->id = $args['id'];
	}

	public function getID() {
		return $this->id;
	}
}
