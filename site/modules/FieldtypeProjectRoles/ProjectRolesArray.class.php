<?php namespace ProcessWire;

/**
 *  Enthält mehrere Rollen, deren Besetzungen jeweils an- oder abgewählt sein können.
 */
class FieldtypeProjectRoleProjectRolesArray extends PageArray {

	public function __construct() {
	}

	public function isValidItem($item) {
		return $item instanceof FieldtypeProjectRoleProjectRole;
	}

	public function add($item) {
		return parent::add($item);
	}

	/**
	 * Prüft, ob zwei RollenArrays gleich sind (benötigt, um isEqual public zu machen)
	 */
	public function isEqualTo($otherRolesArray, $key = ''){
		return $this->isEqual($key, $this, $otherRolesArray);
	}

	protected function isEqual($key, $value1, $value2){
		if(parent::isEqual($key, $value1, $value2)) return true;
		if(!$value1 instanceof FieldtypeProjectRoleProjectRolesArray || !$value2 instanceof FieldtypeProjectRoleProjectRolesArray) return false;

		if($value1->count !== $value2->count) return false;
		foreach($value1 as $item){
			$roleFound = $value2->get('projectRole.id='.$item->projectRole->id);
			if($roleFound === NULL) return false;
			if(!$roleFound->isEqualTo($item)) return false;
		}

		return true;
	}

	public function __toString() {
		$output = '';
		foreach($this as $item) $output .= $item;
		return $output;
	}
}
