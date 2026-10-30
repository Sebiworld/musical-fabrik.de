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
            $this->addComponent('PageCard', ['directory' => '', 'page' => $page]);
        }

        $this->galleriesPage = $this->galleriesService->getGalleriesPage();
        $this->requestUrl = '/api/page' . AppApi::getUrlRelativeToRoot($this->galleriesPage->url);

    }

    public function getAjax($ajaxArgs = []) {
        return $this->galleriesService->getAjax($ajaxArgs);
    }
}
