<?php
namespace ProcessWire;

class BeitragCard extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
	}

	public function getAjax(){
		$output = $this->getAjaxOf($this->page);
		$output['datum'] = $this->page->getUnformatted('zeitpunkt_von');
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

		return $output;
	}
}
