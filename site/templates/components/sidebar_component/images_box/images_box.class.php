<?php
namespace ProcessWire;

class ImagesBox extends TwackComponent {

	public static $sliderIndex = 0; // Counts up to avoid ID conflicts

	public function __construct($args) {
		parent::__construct($args);

		$this->projectPage = $this->getGlobalParameter('projectPage');
		if (isset($args['projectPage']) && $args['projectPage'] instanceof Page && $args['projectPage']->id) {
			$this->projectPage = $args['projectPage'];
		}

		$this->images = new PageArray();
		if (isset($args['images']) && $args['images'] instanceof PageArray) {
			$this->images->add($args['images']);
		}

		if (isset($args['useField']) && !empty($args['useField'])) {
			$this->images->add($this->page->get($args['useField']));
		}

		if (isset($args['title']) && !empty($args['title'])) {
			$this->title = str_replace(array("\n", "\r"), '', $args['title']);
		}

		self::$sliderIndex++;
		$this->sliderIndex = self::$sliderIndex;
	}
}
