<?php

namespace ProcessWire;

class GalleriesTiles extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $filters = array(
            'charLimit' => 150
        );

        // Is a keyword filter set?
        if (wire('input')->get('tags')) {
            $filters['tags'] = wire('input')->get('tags');
        }

        // Is something entered in the free text search?
        if (wire('input')->get('q')) {
            $filters['q'] = wire('input')->get('q');
        }

        if ($this->page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container') instanceof NullPage) {
            $this->addComponent('FiltersComponent', [
                'directory' => 'partials',
                'name'      => 'filters',
                'filters'   => $filters
            ]);
        }

        $this->articlesService       = $this->getService('ArticlesService');
        $galleries                   = $this->articlesService->getGalleries($filters);
        $this->moreAvailable         = $galleries->moreAvailable;
        $this->lastElementIndex      = $galleries->lastElementIndex;
        $this->totalNumber           = $galleries->totalNumber;
        $galleriesPages              = $galleries->galleries;

        foreach ($galleriesPages as $page) {
            $this->addComponent('GalleryCard', ['directory' => 'partials', 'page' => $page]);
        }

        $this->galleriesPage = $this->articlesService->getGalleriesPage();
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
        return $this->articlesService->getAjax();
    }
}
