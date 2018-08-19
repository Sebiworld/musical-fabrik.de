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
}
