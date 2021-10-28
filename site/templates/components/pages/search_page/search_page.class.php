<?php

namespace ProcessWire;

class SearchPage extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $searchPage = wire('pages')->get('template.name=search_page');
        if (!($searchPage instanceof Page) || !$searchPage->id) {
            return false;
        }
        $this->requestUrl = '/api/tpage' . $searchPage->url;

        $this->searchableTemplates = array('article', 'gallery', 'project', 'project_voice_company', 'home', 'default_page', 'area', 'project_roles_container', 'project_role');

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

        if ($this->getService('ProjectService')->getProjectPage() instanceof NullPage) {
            $this->addComponent('FiltersComponent', [
                'directory' => 'partials',
                'name'      => 'filters',
                'filters'   => $filters
            ]);
        }

        $this->pagesService         = $this->getService('PagesService');
        $results                    = $this->pagesService->getResults($filters, [['template', $this->searchableTemplates]]);
        $this->moreAvailable        = $results->moreAvailable;
        $this->lastElementIndex     = $results->lastElementIndex;
        $this->totalNumber          = $results->totalNumber;
        $resultPages                = $results->items;

        $parameters = [];
        if (!empty($args['cardClasses'])) {
            $parameters['classes'] = $args['cardClasses'];
        }

        foreach ($resultPages as $page) {
            $attributes = array(
                'data-id' => $page->id
            );
            $this->addComponent('PageCard', ['directory' => '', 'page' => $page, 'parameters' => $parameters, 'attributes' => $attributes, 'hoverAutoplay' => true]);
        }

        $this->addScript('ajaxmasonry.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/ajaxmasonry.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
    }

    public function getAjax($ajaxArgs = []) {
        return $this->pagesService->getAjax(['selector' => [['template', $this->searchableTemplates]]]);
    }
}
