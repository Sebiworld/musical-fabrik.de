<?php namespace ProcessWire;

/**
 *  Enthält mehrere Rollen, deren Besetzungen jeweils an- oder abgewählt sein können.
 */
class RollenArray extends WireArray {

	public function __construct() {
	}

	public function isValidItem($item) {
		return $item instanceof Rolle;
	}

	public function add($item) {
		return parent::add($item);
	}

	/**
	 * Prüft, ob zwei RollenArrays gleich sind (benötigt, um isEqual public zu machen)
	 */
	public function istGleich($anderesRollenArray, $key = ''){
		return $this->isEqual($key, $this, $anderesRollenArray);
	}

	protected function isEqual($key, $value1, $value2){
		if(parent::isEqual($key, $value1, $value2)) return true;
		if(!$value1 instanceof RollenArray || !$value2 instanceof RollenArray) return false;

		if($value1->count !== $value2->count) return false;
		foreach($value1 as $item){
			$gefundeneRolle = $value2->get('rolle.id='.$item->rolle->id);
			if($gefundeneRolle === NULL) return false;
			if(!$gefundeneRolle->istGleich($item)) return false;
		}

		return true;
	}

	public function __toString() {
		$ausgabe = '';
		foreach($this as $item) $ausgabe .= $item;
		return $ausgabe;
	}
}

