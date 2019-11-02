<?php
namespace ProcessWire;

require_once('base_modal.class.php');

class Modals extends TwackComponent {

	public static $modalIDs = array();

	public function __construct($args) {
		parent::__construct($args);
		$this->modalcomponents = new WireArray();
	}

	/**
	 * Adds a new component to the modals list.
	 */
	public function addComponent($componentname, $args = array()) {
		if (!isset($args['id'])) {
			$args['id'] = Twack::camelCaseToUnderscore($componentname);
		}

		// The ID service ensures that each HTML ID is assigned only once:
		$this->idService = $this->getService('IdService');
		$args['id'] = $this->idService->getID($args['id']);

		$args['name'] = $args['id'];
		$component = parent::addComponent($componentname, $args);
		if (!($component instanceof BaseModal)) {
			throw new ComponentNotInitializedException($componentname, $this->_('All modals must be derived from the class BaseModal.'));
		}

		$this->modalcomponents->add($component);
		return $component;
	}
}
