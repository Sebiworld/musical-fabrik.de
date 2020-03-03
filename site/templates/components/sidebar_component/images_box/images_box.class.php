<?php

namespace ProcessWire;

class ImagesBox extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $galleriesService       = $this->getService('GalleriesService');
		$galleriesOutput       = $galleriesService->getGalleries();

		$this->title = $this->_('Galleries');
		if (isset($args['title']) && !empty($args['title'])) {
			$this->title = str_replace(array("\n", "\r"), '', $args['title']);
		}

        if ($galleriesOutput->items instanceof PageArray && count($galleriesOutput->items) > 0) {
			$this->galleriesPage   = $galleriesService->getGalleriesPage();
			$this->sidebarGallery = $galleriesOutput->items->first();
			$this->addComponent('PageCard', ['directory' => '', 'page' => $this->sidebarGallery, 'autoplay' => true, 'loop' => true]); 
        }
	}
}
