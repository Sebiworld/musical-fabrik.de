<?php
namespace ProcessWire;

class ArticlesCarousel extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$articlesService = $this->getService('ArticlesService');
		$news = $articlesService->getArticles(['charLimit' => 150, 'limit' => 15]);
		$articlePages = $news->articles;
		foreach ($articlePages as $page) {
			if ($page->projectPage instanceof Page && $page->projectPage->color) {
				$page->color = $page->projectPage->color;
			}
			$this->addComponent('ArticleCard', ['directory' => 'partials', 'page' => $page]);
		}

		$this->sliderAlign = 'left';
		if (isset($args['sliderAlign'])) {
			$this->sliderAlign = $args['sliderAlign'];
		} elseif (isset($this->sektion) && $this->sektion) {
			// In sections the slides should be left-justified.
			$this->sliderAlign = 'left';
		}

		$this->articlesPage = $articlesService->getArticlesPage();

		$this->addScript('swiper.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true,
			'inline' => true
        ));
        $this->addScript('legacy/swiper.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true,
			'inline' => true
        ));
	}
}
