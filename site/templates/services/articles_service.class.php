<?php

namespace ProcessWire;

/**
 * Provides methods for reading articles
 */
class ArticlesService extends TwackComponent {

    public function __construct($args) {
        parent::__construct($args);
        $this->projectPage = $this->getService('ProjectService')->getProjectPageWithFallback();
    }

    public function getArticlesPage() {
        $articlesPage = wire('pages')->get('/')->children('template.name=articles_container')->first();
        if ($this->projectPage instanceof Page && $this->projectPage->id) {
            $results = $this->projectPage->find('template.name=articles_container');
            if ($results->count > 0) {
                $articlesPage = $results->first();
            }
        }
        return $articlesPage;
    }

    /**
     * Returns all articles that can be output on this page.
     * @return PageArray
     */
    public function getArticles($args = array()) {
        return $this->getService('PagesService')->getResults($args, [['template', 'article']]);
    }

    public function getAjax() {
        return $this->getService('PagesService')->getAjax([['template', 'article']]);
    }
}
