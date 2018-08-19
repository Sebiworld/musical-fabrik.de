<?php namespace ProcessWire;

/**
 * An individual event item to be part of an EventArray for a Page
 *
 */
class Rolle extends WireData {

	/**
	 * We keep a copy of the $page that owns this rolle so that we can follow
	 * its outputFormatting state and change our output per that state
	 *
	 */
	protected $page;

	/**
	 * Ein Rolle-Objekt braucht zwingend eine zugeordnete Rollen-Seite
	 */
	public function __construct($rolle) {
		$this->set('rolle', $rolle);
		$this->set('besetzungen', '');
	}

	public function set($key, $value) {
		if($key == 'rolle') {
			if(!($value instanceof Page) || !$value->id){
				$seite = wire('pages')->get($value);
				if($seite instanceof Page && $seite->id){
					$value = $seite;
				}
			}
		} else if($key == 'besetzungen') {
			if(!($value instanceof PageArray)){
				$seiten = new PageArray();
				if(is_array($value)){
					foreach($value as $seite){
						$seiten->add($seite);
					}
				}else if($value instanceof Page && $value->id){
					$seiten->add($value);
				}else if(is_string($value)){
					// JSON-String?
					$besetzungenArray = json_decode($value);
					if($besetzungenArray && is_array($besetzungenArray)){
						foreach($besetzungenArray as $besetzungsID){
							$besetzung = wire('pages')->get($besetzungsID);
							if(!$besetzung instanceof Page || !$besetzung->id) continue;
							$seiten->add($besetzung);
						}
					}
				}
				$value = $seiten;
			}
		}

		return parent::set($key, $value);
	}

	public function get($key) {
		$value = parent::get($key);
		return $value;
	}

	public function isValid(){
		if(!$this->rolle instanceof Page || !$this->rolle->id) return false;
		if(!$this->besetzungen instanceof PageArray) return false;
		return true;
	}

	/**
	 * Prüft, ob zwei Rollen gleich sind (benötigt, um isEqual public zu machen)
	 */
	public function istGleich($andereRolle, $key = ''){
		return $this->isEqual($key, $this, $andereRolle);
	}

	protected function isEqual($key, $value1, $value2){
		if(parent::isEqual($key, $value1, $value2)) return true;
		if(!$value1 instanceof Rolle || !$value2 instanceof Rolle) return false;
		if(!$value1->isValid() || !$value2->isValid()) return false;

		if($key == 'rolle') {
			if($value1->rolle->id != $value2->rolle->id) return false;
			return true;
		}else if($key == 'besetzungen') {
			if($value1->besetzungen->count !== $value2->besetzungen->count) return false;
			foreach($value1->besetzungen as $besetzung){
				if(!$value2->besetzungen->has($besetzung)) return false;
			}
			return true;
		}else{
			// Kein Key gesetzt: Rolle und Besetzungen vergleichen
			if($value1->rolle->id != $value2->rolle->id) return false;
			if($value1->besetzungen->count !== $value2->besetzungen->count) return false;
			foreach($value1->besetzungen as $besetzung){
				if(!$value2->besetzungen->has($besetzung)) return false;
			}
		}

		return true;
	}

	/**
	 * Return a string representing this event
	 *
	 */
	public function __toString() {
		$ausgabe = '<p>';
		$ausgabe .= '<strong>'.$this->rolle->title.'</strong>';

		if($this->besetzungen && $this->besetzungen instanceof PageArray && count($this->besetzungen) > 0){
			$ausgabe .= '<br/><em>';
			foreach($this->besetzungen as $key => $besetzung){
				if($key > 0) $ausgabe .= ', ';
				$ausgabe .= $besetzung->title;
			}
			$ausgabe .= '</em>';
		}
		$ausgabe .= '</p>';

		return $ausgabe;
	}

}

