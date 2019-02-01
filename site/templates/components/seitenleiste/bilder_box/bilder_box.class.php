<?php
namespace ProcessWire;

class BilderBox extends TwackComponent {

	public static $sliderIndex = 0; // Wird hochgezählt, um ID-Konflikte zu vermeiden

	public function __construct($args) {
		parent::__construct($args);

		$projektseite = $this->getGlobalParameter('projektseite');
		if (isset($args['projektseite']) && $args['projektseite'] instanceof Page && $args['projektseite']->id) {
			$projektseite = $args['projektseite'];
		}

		$this->bilder = new PageArray();
		if (isset($args['bilder']) && $args['bilder'] instanceof PageArray) {
			$this->bilder->add($args['bilder']);
		}

		if (isset($args['nutzefeld']) && !empty($args['nutzefeld'])) {
			$this->bilder->add($this->page->get($args['nutzefeld']));
		}

		if (isset($args['titel']) && !empty($args['titel'])) {
			$this->titel = str_replace(array("\n", "\r"), '', $args['titel']);
		}

		self::$sliderIndex++;
		$this->sliderIndex = self::$sliderIndex;
	}
}
