<?php
namespace ProcessWire;

/**
 * Basic functions that every modal needs. Must be integrated by modal components via "extends BasicModal".
 */
abstract class BaseModal extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if (!isset($args['id']) || !is_string($args['id'])) {
			throw new ComponentNotInitializedException('Modal', $this->_('No valid ID was passed to the modal.'));
		}
		$this->id = $args['id'];
	}

	public function getID() {
		return $this->id;
	}
}
