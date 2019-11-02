<?php

namespace ProcessWire;

class SearchPage extends TwackComponent {
	
    protected $overviewPagesService;

    public function __construct($args) {
        parent::__construct($args);

        $this->overviewPagesService = $this->getService('OverviewPagesService');

        $searchableTemplates = array('article', 'project', 'home', 'default_page');

        $searchPage = wire('pages')->get('template.name=search_page');
        if ($searchPage instanceof Page && $searchPage->id) {
            $this->searchPage = $searchPage;
        }

        $this->query   = wire('sanitizer')->text(wire('input')->get('q'));
        $this->results = wire('pages')->find('template.name=' . implode('|', $searchableTemplates) . ', title%=' . $this->query);
        $this->results = $this->overviewPagesService->format(
            $this->results,
            ['limit' => 150]
        );
    }
}
