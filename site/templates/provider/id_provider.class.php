<?php
namespace ProcessWire;

/**
 * Stellt unique IDs zur Verfügung (Benötigt für z.B. Formulare, Akkordeons und Modals).
 */
class IdProvider extends TwackComponent {

	protected $ids;

	public function __construct($args) {
		parent::__construct($args);

		$this->ids = array();
	}

	/**
	 * Liefert eine unique ID auf der aktuellen Seite
	 * @param  string $forderung
	 * @return string
	 */
	public function getID($forderung = 'id') {
		if (!isset($this->ids[$forderung]) && $forderung != 'id') {
			$this->ids[$forderung] = $forderung;
			return $this->ids[$forderung];
		}

		$counter = 0;
		$wert = $forderung . '-' . $counter;
		while (isset($this->ids[$wert])) {
			$counter = $counter + 1;
			$wert = $forderung . '-' . $counter;
		}
		
		$ids = $this->ids;
		$ids[$wert] = $wert;
		$this->ids = $ids;

		return $this->ids[$wert];
	}
}
