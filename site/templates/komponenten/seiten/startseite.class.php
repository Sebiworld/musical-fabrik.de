<?php
namespace ProcessWire;

/**
 * Generiert aus der Startseite_inhalte-Repeatermatrix Inhaltskomponenten
 */
class Startseite extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		// Onepage-Elemente einlesen:
		if ($this->page->hasField('onepage_elemente') && $this->page->onepage_elemente->count() > 0) {
			$general = $this->getGlobalComponent('general');
			foreach ($this->page->onepage_elemente as $element) {
				$general->addComponent($element->template->name, ['directory' => 'sektionen', 'page' => $element]);
			}
		}

		$this->addStyle(wire('config')->urls->templates . 'assets/css/home.min.css', true, true);
	}
}
