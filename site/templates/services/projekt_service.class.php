<?php
namespace ProcessWire;

class ProjektService extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->projektseite = $this->page;
		if ($this->projektseite->template->name != 'projekt') {
			$this->projektseite = $this->page->closest('template=projekt');
		}
		if (isset($args['projektseite']) && $args['projektseite'] instanceof Page && $args['projektseite']->id) {
			$this->projektseite = $args['projektseite'];
		}
		if (!($this->projektseite instanceof Page) || !$this->projektseite->id) {
			$this->projektseite = wire('pages')->get('/');
		}
	}

	public function getProjektseite(){
		return $this->projektseite;
	}

	public function getPortraitsContainer(){
		return wire('pages')->findOne('template.name=portraits_container, include=hidden, has_parent='.$this->projektseite->id);
	}

	public function getStaffelnContainer(){
		return wire('pages')->findOne('template.name=staffeln_container, include=hidden, has_parent='.$this->projektseite->id);
	}

	public function getBesetzungenContainer(){
		return wire('pages')->findOne('template.name=besetzungen_container, include=hidden, has_parent='.$this->projektseite->id);
	}
}
