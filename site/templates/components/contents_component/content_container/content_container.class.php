<?php
namespace ProcessWire;

class ContentContainer extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
			'type' => 'text',
			'id' => $this->page->id,
			'depth' => $this->page->depth,
			'title' => $this->page->title,
			'hide_title' => $this->page->hide_title,
			'classes' => $this->page->classes
		];

		return $output;
	}
}
