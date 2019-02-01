<?php
namespace ProcessWire;

require_once __DIR__ . '/../rolle_basis.class.php';

/**
 * Stellt die Portraits der Rolle als Block dar.
 */
class RolleAlsBlockMitRollen extends RolleBasis {

	public function __construct($args) {
		parent::__construct($args);
	}

	/**
	 * Liefert alle Portraits der Unterrollen
	 * @return PageArray
	 */
	public function getPortraits(){
		$portraits = new WireArray();

		foreach ($this->page->darsteller as $darstellereintrag) {
			foreach($darstellereintrag->portraits as $portrait){
				if(!$portraits->has('id='.$portrait->id)){
					$portraits->add($this->getNewComponent('Portrait', array(
						'directory' => 'projekt_rolle',
						'page' => $portrait,
						'root' => true
					)));
				}

				$portraits->get('id='.$portrait->id)->addStaffelBesetzungKombination($darstellereintrag->staffeln, $darstellereintrag->besetzungen, $this->page);
			}
		}

		foreach($this->unterrollen as $rolle){
			foreach ($rolle->darsteller as $darstellereintrag) {
				foreach($darstellereintrag->portraits as $portrait){
					if(!$portraits->has('id='.$portrait->id)){
						$portraits->add($this->getNewComponent('Portrait', array(
							'directory' => 'projekt_rolle',
							'page' => $portrait,
						)));
					}

					$portraits->get('id='.$portrait->id)->addStaffelBesetzungKombination($darstellereintrag->staffeln, $darstellereintrag->besetzungen, $rolle);
				}
			}
		}

		return $portraits;
	}

	/**
	 * Liefert einen HTML-String mit der Ausgabe aller Unterrollen
	 * @return string
	 */
	public function renderUnterrollen(){
		// Keine Ausgabe der Unterrollen, da diese schon im View mit eingearbeitet sind.
		return '';
	}
}
