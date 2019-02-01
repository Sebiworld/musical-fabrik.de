<?php
namespace ProcessWire;


class Portrait extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if($this->page->template->name !== 'portrait'){
			throw new ComponentNotInitializedException("Es wurde keine valide Portrait-Seite übergeben.");
		}

		$this->root = false;
		if(isset($args['root']) && $args['root']){
			$this->root = true;
		}

		$this->bildService = $this->getService('BildService');
		$this->projektService = $this->getService('ProjektService');

		// Attribute aus der Seite auslesen, die zum Sortieren des WireArrays genutzt werden können:
		$this->setArray($this->page->getArray());
		$this->id = $this->page->id;
		$this->name = $this->page->name;
		$this->title = $this->page->title;

		$this->staffeln = new WireArray();
		$this->rollen = new WireArray();

		// Alle Staffeln, die es in dem Projekt gibt:
		$staffelContainer = $this->projektService->getStaffelnContainer();
		$this->alleStaffeln = $staffelContainer->children();

		// Alle Besetzungen, die es in dem Projekt gibt:
		$besetzungenContainer = $this->projektService->getBesetzungenContainer();
		$this->alleBesetzungen = $besetzungenContainer->children();

		// TODO: Popover bei Klick mit Detailinfos zum Portrait
	}

	public function addStaffelBesetzungKombination($staffeln, $besetzungen, $rollen = false){
		// Wenn kein Staffeln-Pagearray übergeben wird, gilt die Rolle für alle Staffeln:
		if(!($staffeln instanceof PageArray)){
			$staffeln = $this->alleStaffeln;
		}

		// Wenn kein Besetzungen-Pagearray übergeben wird, gilt die Rolle für alle Besetzungen:
		if(!($besetzungen instanceof PageArray)){
			$besetzungen = $this->alleBesetzungen;
		}

		if($rollen instanceof Page && $rollen->id){
			$tmpRolle = $rollen;
			$rollen = new PageArray();
			$rollen->add($tmpRolle);
		}elseif(!($rollen instanceof PageArray)){
			$rollen = new PageArray();
		}

		foreach($staffeln as $staffelPage){
			// Sub-Selektoren funktionieren nicht bei Elementen,
			// die nur im Speicher vorhanden sind. Um Sub-Selektoren zu
			// verwenden, müssten sie in der DB gespeichert sein. Deshalb
			// werden die Staffeln und Besetzungen auf erster Ebene abgelegt.
			$this->{'staffel_'.$staffelPage->id} = 1;

			if(!$this->staffeln->has('id=' . $staffelPage->id)){
				$staffel = new WireData();
				$staffel->setArray($staffelPage->getArray());
				$staffel->id = $staffelPage->id;
				$staffel->name = $staffelPage->name;
				$staffel->title = $staffelPage->title;
				$staffel->besetzungen = new WireArray();
				$this->staffeln->add($staffel);
			}

			foreach($besetzungen as $besetzungPage){
				$this->{'besetzung_'.$besetzungPage->id} = 1;
				$this->{'staffel_'.$staffelPage->id.'_'.$besetzungPage->id} = 1;

				if(!$this->staffeln->get('id=' . $staffelPage->id)->besetzungen->has('id='.$besetzungPage->id)){
					$besetzung = new WireData();
					$besetzung->setArray($besetzungPage->getArray());
					$besetzung->id = $besetzungPage->id;
					$besetzung->name = $besetzungPage->name;
					$besetzung->title = $besetzungPage->title;
					$besetzung->rollen = new WireArray();
					$this->staffeln->get('id=' . $staffelPage->id)->besetzungen->add($besetzung);

					if(!($this->{'staffel_'.$staffelPage->id.'_besetzungen'} instanceof WireArray)){
						$this->{'staffel_'.$staffelPage->id.'_besetzungen'} = new WireArray();
					}
					$this->{'staffel_'.$staffelPage->id.'_besetzungen'}->add($besetzung);
				}

				// Rollen zur Besetzung hinzufügen:
				foreach($rollen as $rollePage){
					$this->{'rolle_'.$rollePage->id} = 1;
					$this->{'staffel_'.$staffelPage->id.'_'.$besetzungPage->id.'_'.$rollePage->id} = 1;
					$this->{'staffel_'.$staffelPage->id.'_rolle_'.$rollePage->id} = 1;
					$this->{'besetzung_'.$besetzungPage->id.'_rolle_'.$rollePage->id} = 1;

					if($this->staffeln->get('id=' . $staffelPage->id)->besetzungen->get('id='.$besetzungPage->id)->rollen->has('id='.$rollePage->id)){
						continue;
					}
					$rolle = new WireData();
					$rolle->setArray($rollePage->getArray());
					$rolle->id = $rollePage->id;
					$rolle->name = $rollePage->name;
					$rolle->title = $rollePage->title;
					$this->staffeln->get('id=' . $staffelPage->id)->besetzungen->get('id='.$besetzungPage->id)->rollen->add($rolle);

					if(!($this->{'staffel_'.$staffelPage->id.'_'.$besetzungPage->id.'_rollen'} instanceof WireArray)){
						$this->{'staffel_'.$staffelPage->id.'_'.$besetzungPage->id.'_rollen'} = new WireArray();
					}
					$this->{'staffel_'.$staffelPage->id.'_'.$besetzungPage->id.'_rollen'}->add($rolle);

					if(!($this->{'staffel_'.$staffelPage->id.'_rollen'} instanceof WireArray)){
						$this->{'staffel_'.$staffelPage->id.'_rollen'} = new WireArray();
					}
					$this->{'staffel_'.$staffelPage->id.'_rollen'}->add($rolle);

					if(!($this->{'besetzung_'.$besetzungPage->id.'_rollen'} instanceof WireArray)){
						$this->{'besetzung_'.$besetzungPage->id.'_rollen'} = new WireArray();
					}
					$this->{'besetzung_'.$besetzungPage->id.'_rollen'}->add($rolle);
				}
			}
		}
	}

	public function renderWithSubtitle($subtitle){
		$this->subtitle = $subtitle;
		return parent::render();
	}

	public function render(){
		$this->subtitle = false;
		return parent::render();
	}
}