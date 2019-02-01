<?php
namespace ProcessWire;

require_once('basis_modal.class.php');

class Modals extends TwackComponent {

	public static $modalIDs = array();

	public function __construct($args) {
		parent::__construct($args);
		$this->modalkomponenten = new WireArray();
	}

	/**
	 * Fügt eine neue Komponente zur Modals-Liste hinzu.
	 */
	public function addComponent($componentname, $args = array()) {
		if (!isset($args['id'])) {
			$args['id'] = Twack::camelCaseToUnderscore($componentname);
		}

		// Der ID-Service stellt sicher, dass jede HTML-ID nur einmal vergeben wird:
		$this->idService = $this->getService('IdService');
		$args['id'] = $this->idService->getID($args['id']);

		$args['name'] = $args['id'];
		$komponente = parent::addComponent($componentname, $args);
		if (!($komponente instanceof BasisModal)) {
			throw new ComponentNotInitializedException($componentname, 'Alle Modals müssen von der Klasse BasisModal abgeleitet werden.');
		}

		$this->modalkomponenten->add($komponente);
		return $komponente;
	}
}
