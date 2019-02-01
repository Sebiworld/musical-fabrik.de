<?php
namespace ProcessWire;

class TerminCard extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->projektService = $this->getService("ProjektService");
	}

	public function getAjax(){
		$output = $this->getAjaxOf($this->page);
		if(isset($output['url'])) unset($output['url']);
		if(isset($output['httpUrl'])) unset($output['httpUrl']);
		if(isset($output['template'])) unset($output['template']);
		if(isset($output['name'])) unset($output['name']);

		$output['zeitpunkt_von'] = $this->page->getUnformatted('zeitpunkt_von');
		$output['zeitpunkt_bis'] = $this->page->getUnformatted('zeitpunkt_bis');
		$output['einleitung'] = $this->page->einleitung;

		if(wire('input')->get('htmlAusgabe')){
			$output['html'] = $this->renderView();
		}

		if($this->page->titelbild){
			$output['titelbild'] = $this->getAjaxOf($this->page->titelbild->height(300));
		}

		if($this->page->farbe){
			$output['farbe'] = $this->page->farbe;
		}

		// Sollen die Zeiträume des Termins ausgegeben werden?
		if(!wire('input')->get('versteckeZeitraeume')){
			$output['zeitraeume'] = array();
			foreach($this->page->zeitraeume->sort('zeitpunkt_von') as $zeitraum){
				$zr = array(
					'id' => $zeitraum->id,
					'title' => $zeitraum->title,
					'beschreibung' => $zeitraum->beschreibung
				);

				// Zeiträume:
				$zr['zeitpunkt_von'] = $zeitraum->getUnformatted('zeitpunkt_von');
				$zr['zeitpunkt_bis'] = $zeitraum->getUnformatted('zeitpunkt_bis');

				// Rollen:
				$zr['rollen'] = array();
				foreach($zeitraum->rollen as $rolle){
					$rolleOutput = array(
						'id' => $rolle->rolle->id,
						'besetzungen' => $rolle->besetzungen->explode('id')
					);

					if($rolle->besetzungen->count !== $this->projektService->getBesetzungenContainer()){

					}

					$zr['rollen'][$rolle->rolle->id] = $rolleOutput;
				}

				// Kategorien:
				$zr['kategorien'] = $zeitraum->termin_kategorien->explode('id');

				// Besetzung:
				if($zeitraum->besetzung instanceof Page && $zeitraum->besetzung->id){
					$zr['spielende_besetzung'] = array(
						'id' => $zeitraum->besetzung->id,
						'title' => $zeitraum->besetzung->title,
						'name' => $zeitraum->besetzung->name
					);
				}

				// Ort:
				if($zeitraum->ort instanceof Page && $zeitraum->ort->id){
					$zr['ort'] = $zeitraum->ort->id;
				}

				$output['zeitraeume'][] = $zr;
			}
		}

		return $output;
	}
}
