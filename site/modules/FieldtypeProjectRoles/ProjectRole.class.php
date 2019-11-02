<?php namespace ProcessWire;

/**
 * An individual event item to be part of an EventArray for a Page
 *
 */
class FieldtypeProjectRoleProjectRole extends WireData {

	/**
	 * We keep a copy of the $page that owns this rolle so that we can follow
	 * its outputFormatting state and change our output per that state
	 *
	 */
	protected $page;

	/**
	 * Ein Rolle-Objekt braucht zwingend eine zugeordnete Rollen-Seite
	 */
	public function __construct($projectRole) {
		$this->set('projectRole', $projectRole);
		$this->set('casts', '');
	}

	public function set($key, $value) {
		if($key == 'projectRole') {
			if(!($value instanceof Page) || !$value->id){
				$page = wire('pages')->get($value);
				if($page instanceof Page && $page->id){
					$value = $page;
				}
			}
		} else if($key == 'casts') {
			if(!($value instanceof PageArray)){
				$pages = new PageArray();
				if(is_array($value)){
					foreach($value as $page){
						$pages->add($page);
					}
				}else if($value instanceof Page && $value->id){
					$pages->add($value);
				}else if(is_string($value)){
					// JSON-String?
					$castsArray = json_decode($value);
					if($castsArray && is_array($castsArray)){
						foreach($castsArray as $castID){
							$cast = wire('pages')->get($castID);
							if(!$cast instanceof Page || !$cast->id) continue;
							$pages->add($cast);
						}
					}
				}
				$value = $pages;
			}
		}

		return parent::set($key, $value);
	}

	public function get($key) {
		$value = parent::get($key);
		return $value;
	}

	public function isValid(){
		if(!$this->projectRole instanceof Page || !$this->projectRole->id) return false;
		if(!$this->casts instanceof PageArray) return false;
		return true;
	}

	/**
	 * Prüft, ob zwei Rollen gleich sind (benötigt, um isEqual public zu machen)
	 */
	public function isEqualTo($otherRole, $key = ''){
		return $this->isEqual($key, $this, $otherRole);
	}

	protected function isEqual($key, $value1, $value2){
		if(parent::isEqual($key, $value1, $value2)) return true;
		if(!$value1 instanceof FieldtypeProjectRoleProjectRole || !$value2 instanceof FieldtypeProjectRoleProjectRole) return false;
		if(!$value1->isValid() || !$value2->isValid()) return false;

		if($key == 'projectRole') {
			if($value1->projectRole->id != $value2->projectRole->id) return false;
			return true;
		}else if($key == 'casts') {
			if($value1->casts->count !== $value2->casts->count) return false;
			foreach($value1->casts as $cast){
				if(!$value2->casts->has($cast)) return false;
			}
			return true;
		}else{
			// Kein Key gesetzt: Rolle und Besetzungen vergleichen
			if($value1->projectRole->id != $value2->projectRole->id) return false;
			if($value1->casts->count !== $value2->casts->count) return false;
			foreach($value1->casts as $cast){
				if(!$value2->casts->has($cast)) return false;
			}
		}

		return true;
	}

	/**
	 * Return a string representing this event
	 *
	 */
	public function __toString() {
		$output = '<p>';
		$output .= '<strong>'.$this->projectRole->title.'</strong>';

		if($this->casts && $this->casts instanceof PageArray && count($this->casts) > 0){
			$output .= '<br/><em>';
			foreach($this->casts as $key => $cast){
				if($key > 0) $output .= ', ';
				$output .= $cast->title;
			}
			$output .= '</em>';
		}
		$output .= '</p>';

		return $output;
	}

}
