<?php

namespace ProcessWire;

class GalleriesTiles extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $filters = array(
            'charLimit' => 150
        );

        $this->galleriesService       = $this->getService('GalleriesService');
        $galleries                   = $this->galleriesService->getGalleries($filters);
        $this->moreAvailable         = $galleries->moreAvailable;
        $this->lastElementIndex      = $galleries->lastElementIndex;
        $this->totalNumber           = $galleries->totalNumber;
        $galleriesPages              = $galleries->items;

        foreach ($galleriesPages as $page) {
            $this->addComponent('GalleryCard', ['directory' => 'partials', 'page' => $page]);
        }

        $this->galleriesPage = $this->galleriesService->getGalleriesPage();
        $this->requestUrl = '/api/page' . $this->galleriesPage->url;

        $this->addScript('ajaxmasonry.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/ajaxmasonry.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
    }

    public function getAjax() {
        return $this->galleriesService->getAjax();
    }
}
