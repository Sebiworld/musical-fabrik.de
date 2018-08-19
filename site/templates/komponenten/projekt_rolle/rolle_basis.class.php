<?php
namespace ProcessWire;

/*
* Vorgabe, wie ein RolleBasis aussehen muss:
* (So können statt Bootstrap auch noch andere Ausgabe-Varianten definiert werden)
*/
if (!class_exists('Processwire\RolleBasis')) {
	abstract class RolleBasis extends TwackComponent {

		public function __construct($args) {
			parent::__construct($args);

			// Provider holen, die alle Rollen brauchen:
			$this->bildProvider = $this->getProvider('BildProvider');
			$this->projektProvider = $this->getProvider('ProjektProvider');
			$this->rollenProvider = $this->getProvider('RollenProvider');
			$this->idProvider = $this->getProvider('IdProvider');

			// Soll der Titel der Rolle angezeigt werden? Der Titel der übergeordneten Rolle wird mit Beschreibung schon durch die Standardseite ausgegeben, deshalb muss der Titel standardmäßig nicht ausgespielt werden.
			$this->titelAnzeigen = false;
			if(isset($args['titelAnzeigen']) && $args['titelAnzeigen']){
				$this->titelAnzeigen = true;
			}

			// Alle Unterrollen, die unter dieser Rolle angeordnet sind:
			$this->unterrollen = $this->page->children('template.name=rolle');
			if(isset($args['unterrollen']) && !empty($args['unterrollen']->id)){
				$this->unterrollen = $args['unterrollen'];
			}

			// Alle verfügbaren Staffeln im Projekt:
			$staffelContainer = $this->projektProvider->getStaffelnContainer();
			$this->staffeln = $staffelContainer->children();

			// Soll eine spezielle Staffel angezeigt werden?
			$this->staffel = $this->staffeln->first();
			if(isset($args['staffel']) && !empty($args['staffel']->id)){
				$this->staffel = $args['staffel'];
			}

			// Alle Besetzungen, die es in dem Projekt gibt:
			$besetzungenContainer = $this->projektProvider->getBesetzungenContainer();
			$this->alleBesetzungen = $besetzungenContainer->children();

			$this->portraits = new WireArray();
			if(!empty($args['portraits']) && $args['portraits'] instanceof WireArray){
				$this->portraits = $args['portraits'];
			}else{
				$this->portraits = $this->getPortraits();
			}
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

		/**
		 * Generiert für jede Unterrolle eine Komponente
		 * @return WireArray<TwackComponent>
		 */
		public function getUnterrollenKomponenten(){
			$komponenten = new WireArray();
			foreach($this->unterrollen as $rolle){
				$komponenten->add($this->getNewComponent(
					'rolle_'.$this->anzeigemodus,
					array(
						'directory' => 'projekt_rolle',
						'parameters' => $this->getArray(),
						'staffel' => $this->staffel,
						'portraits' => $this->portraits->find('staffel_'.$this->staffel->id.'_rolle_'.$rolle->id.'=1')
					)
				));
				// $komponenten->add($this->getNewComponent('ProjektRolle', array(
				// 	'rolle' => $rolle,
				// 	'parameters' => array(),
				// 	'staffel' => $this->staffel,
				// 	'titelAnzeigen' => true,
				// 	'portraits' => $this->portraits->find('staffel_'.$this->staffel->id.'_rolle_'.$rolle->id.'=1')
				// )));
			}
			return $komponenten;
		}

		/**
		 * Liefert einen HTML-String mit der Ausgabe aller Unterrollen
		 * @return string
		 */
		public function renderUnterrollen(){
			$output = 'TEST';

			foreach($this->getUnterrollenKomponenten() as $komponente){
				try{
					$output .= $komponente->render();
				}catch(\Exception $e){}
			}

			echo "<pre>";
			// var_dump($output);
			echo "</pre>";
			$output = '';

			return $output;
		}
	}
}
