<?php
namespace ProcessWire;

require_once __DIR__ . '/rolle_basis.class.php';

/**
 * Stellt eine Rolle dar (template rollen_container oder rolle).
 * Sucht die Portraits für diese Rolle und die Unterrollen heraus. Generiert einen Staffel-Container wenn nötig.
 */
class ProjektRolle extends RolleBasis {

	const DEFAULT_ANZEIGEMODUS = 'als_block';

	public function __construct($args) {
		parent::__construct($args);

		$this->anzeigemodus = false;
		if(!empty($args['anzeigemodus'])){
			$this->anzeigemodus = $args['anzeigemodus'];
		}else if(!empty($this->page->rolle_anzeige_optionen)){
			$this->anzeigemodus = $this->page->rolle_anzeige_optionen->name;
		}else{
			//  Wenn kein Anzeigemodus gesetzt ist, soll der des Elternelements genommen werden.
			$elternElement = $this->page->closest('template.name=rollen_container|rolle, rolle_anzeige_optionen!=""');
			if ($elternElement instanceof Page && $elternElement->id && $elternElement->template->hasField('rolle_anzeige_optionen') && $elternElement->rolle_anzeige_optionen->name) {
				$this->anzeigemodus = $elternElement->rolle_anzeige_optionen->name;
			}
		}

		if(!$this->anzeigemodus){
			$this->anzeigemodus = SELF::DEFAULT_ANZEIGEMODUS;
		}

		$rollenkomponente = $this->getNewComponent(
			'rolle_'.$this->anzeigemodus,
			array(
				'parameters' => $this->getArray(),
				'staffel' => $this->staffel
			)
		);
		$this->ausgabe = $rollenkomponente;

		if($this->portraits->has('staffeln.count<' . $this->staffeln->count())){
			// Es gibt Portraits, die nicht allen Staffeln zugewiesen sind. Staffelausgabe aktivieren:
			$this->ausgabe = $this->getNewComponent(
				'RolleStaffeln',
				array(
					'rolle' => $rollenkomponente,
					'parameters' => $this->getArray(),
					'anzeigemodus' => $this->anzeigemodus,
					'staffel' => $this->staffel,
					'portraits' => $this->portraits
				)
			);
		}

		$this->addStyle('rolle-seite.css', array(
            'path'     => wire('config')->urls->templates . 'assets/css/',
			'absolute' => true,
			'inline' => true
        ));
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
							'page' => $portrait
						)));
					}

					$portraits->get('id='.$portrait->id)->addStaffelBesetzungKombination($darstellereintrag->staffeln, $darstellereintrag->besetzungen, $rolle);
				}
			}
		}

		return $portraits;
	}
}
