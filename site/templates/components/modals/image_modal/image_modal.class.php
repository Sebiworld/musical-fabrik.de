<?php
namespace ProcessWire;

class ImageModal extends BaseModal {
	public function __construct($args) {
		parent::__construct($args);

		if (isset($args['images'])) {
			$this->images = $args['images'];
		}

		$this->singleImage = isset($args['singleImage']) && !!$args['singleImage'];
	}
}
