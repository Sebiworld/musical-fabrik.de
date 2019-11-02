<?php
namespace ProcessWire;

/**
 * Provides unique IDs (needed for e.g. forms, accordions and modals).
 */
class IdService extends TwackComponent {

	protected $ids;

	public function __construct($args) {
		parent::__construct($args);
		$this->ids = array();
	}

	/**
	 * Returns a unique ID on the current page
	 * @param  string $requestedId
	 * @return string
	 */
	public function getID($requestedId = 'id') {
		if (!isset($this->ids[$requestedId]) && $requestedId != 'id') {
			$this->ids[$requestedId] = $requestedId;
			return $this->ids[$requestedId];
		}

		$counterChar = 'a';
		$value = $requestedId . '-' . $counterChar;
		while (isset($this->ids[$value])) {
			// Increments to the next letter in the alphabet: When z is reached, aa follows.
			$value = $requestedId . '-' . (++$counterChar);
		}

		$ids = $this->ids;
		$ids[$value] = $value;
		$this->ids = $ids;

		return $this->ids[$value];
	}
}
