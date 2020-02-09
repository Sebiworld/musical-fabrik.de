<?php
namespace ProcessWire;

/**
 * Generated from the start page_content repeater matrix Content components
 */
class HomePage extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		// Read Onepage Elements:
		if ($this->page->hasField('onepage_elements') && $this->page->onepage_elements->count() > 0) {
			$general = $this->getGlobalComponent('general');
			foreach ($this->page->onepage_elements as $element) {
				$general->addComponent($element->template->name, ['directory' => 'sections', 'page' => $element]);
			}
		}

		// $this->addStyle('home_page.css', array(
        //     'path'     => wire('config')->urls->templates . 'assets/css/',
		// 	'absolute' => true
        // ));
	}
}
