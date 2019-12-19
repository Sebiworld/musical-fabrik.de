<?php

namespace ProcessWire;

class ImagesBox extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $articlesService       = $this->getService('ArticlesService');
		$galleriesOutput       = $articlesService->getGalleries();

		$this->title = $this->_('Galleries');
		if (isset($args['title']) && !empty($args['title'])) {
			$this->title = str_replace(array("\n", "\r"), '', $args['title']);
		}

        if ($galleriesOutput->galleries instanceof PageArray && count($galleriesOutput->galleries) > 0) {
			$this->galleriesPage   = $articlesService->getGalleriesPage();
			$this->sidebarGallery = $galleriesOutput->galleries->first();
			$this->addComponent('GalleryCard', ['directory' => 'partials', 'page' => $this->sidebarGallery, 'autoplay' => true]);
        }
	}
}
