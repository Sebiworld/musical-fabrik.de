<?php
namespace ProcessWire;

class SchlagwoerterFeld extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$nutzeFeld = 'schlagwoerter';
		if ($this->page->template->hasField($nutzeFeld) && $this->page->get($nutzeFeld) instanceof PageArray) {
			$this->schlagwoerter = $this->page->get($nutzeFeld);
		}
	}

	public function getAjax(){
		$output = array();

		if($this->schlagwoerter instanceof PageArray){
			foreach($this->schlagwoerter as $schlagwort){
				$output[] = array(
					'id' => $schlagwort->id,
					'title' => $schlagwort->title,
					'name' => $schlagwort->name
				);
			}
		}

		return $output;
	}
}
