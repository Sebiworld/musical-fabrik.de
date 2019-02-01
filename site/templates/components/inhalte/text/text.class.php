<?php
namespace ProcessWire;

class Text extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
	}

	public function getAjax(){
		$output = array(
			'type' => 'text',
			'text' => $this->page->text
		);

		return $output;
	}
}
