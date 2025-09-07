<?php
namespace ProcessWire;

class ArticlesCarousel extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$parentPage = $this->page;
		if($this->projectPage instanceof Page && $this->projectPage->id){
			$parentPage = $this->projectPage;
		} else if(!empty($this->page->_pageTableParent)){
			$parentPage = $this->page->_pageTableParent;
		}

		$articlesService = $this->getService('ArticlesService');
		$news = $articlesService->getArticles(['charLimit' => 150, 'limit' => 15], $parentPage);
		$articlePages = $news->items;

		$parameters = [];
		if (!empty($args['cardClasses'])) {
			$parameters['classes'] = $args['cardClasses'];
		}

		foreach ($articlePages as $page) {
			$this->addComponent('PageCard', ['directory' => '', 'page' => $page, 'parameters' => $parameters]);
		}

		$this->sliderAlign = 'left';
		if (isset($args['sliderAlign'])) {
			$this->sliderAlign = $args['sliderAlign'];
		} elseif (isset($this->sektion) && $this->sektion) {
			// In sections the slides should be left-justified.
			$this->sliderAlign = 'left';
		}

		$this->articlesPage = $articlesService->getArticlesPage();

		$this->addScript('swiper.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true,
			'inline' => true
		]);
		$this->addScript('legacy/swiper.js', [
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true,
			'inline' => true
		]);
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
			'items' => []
		];

		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax($ajaxArgs);
				if (empty($ajax)) {
					continue;
				}
				$output['items'][] = $ajax;
			}
		}

		return $output;
	}
}
