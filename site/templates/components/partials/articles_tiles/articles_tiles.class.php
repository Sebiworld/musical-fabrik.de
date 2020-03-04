<?php

namespace ProcessWire;

class ArticlesTiles extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $filters = array(
            'charLimit' => 150
        );

        $this->articlesService      = $this->getService('ArticlesService');
        $articles                   = $this->articlesService->getArticles($filters);
        $this->moreAvailable        = $articles->moreAvailable;
        $this->lastElementIndex     = $articles->lastElementIndex;
        $this->totalNumber          = $articles->totalNumber;
        $articlesPages              = $articles->items;

        $parameters = [];
        if(!empty($args['cardClasses'])){
            $parameters['classes'] = $args['cardClasses'];
        }

        foreach ($articlesPages as $page) {
            $this->addComponent('PageCard', ['directory' => '', 'page' => $page, 'parameters' => $parameters]);
        }

        $this->articlesPage = $this->articlesService->getArticlesPage();
        $this->requestUrl = '/api/page' . $this->articlesPage->url;

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
        return $this->articlesService->getAjax($ajaxArgs);
    }
}
