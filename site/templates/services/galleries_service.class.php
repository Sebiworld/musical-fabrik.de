<?php

namespace ProcessWire;

/**
 * Provides methods for reading galleries
 */
class GalleriesService extends TwackComponent {

    public function __construct($args) {
        parent::__construct($args);
        $this->projectPage = $this->getService('ProjectService')->getProjectPageWithFallback();
    }

    public function getGalleriesPage() {
        $galleriesPageg = wire('pages')->get('/')->children('template.name=galleries_container')->first();
        if ($this->projectPage instanceof Page && $this->projectPage->id) {
            $results = $this->projectPage->find('template.name=galleries_container');
            if ($results->count > 0) {
                $galleriesPageg = $results->first();
            }
        }
        return $galleriesPageg;
    }

    /**
     * Returns all galleries that can be output on this page.
     * @return PageArray
     */
    public function getGalleries($args = array()) {
        return $this->getService('PagesService')->getResults($args, [['template', 'gallery']]);
    }

    public function getAjax() {
        return $this->getService('PagesService')->getAjax([['template', 'gallery']]);
    }
}
