<?php
namespace ProcessWire;

/**
 * Provides methods for reading articles
 */
class ArticlesService extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);
		$this->projectPage = $this->getService('ProjectService')->getProjectPage();
	}

	public function getArticlesPage($page = false) {
		$projectPage = $this->projectPage;
		if ($page instanceof Page && $page->id) {
			$projectPage = $this->getService('ProjectService')->getProjectPage($page);
		}

		if (!($projectPage instanceof Page) || !$projectPage->id) {
			return wire('pages')->get('/')->children('template.name=articles_container')->first();
		}

		$results = $projectPage->find('template.name=articles_container');

		if ($results->count <= 0) {
			return wire('pages')->get('/')->children('template.name=articles_container')->first();
		}

		return $results->first();
	}

	/**
	 * Returns all articles that can be output on this page.
	 * @return PageArray
	 */
	public function getArticles($args = [], $page = false) {
		return $this->getService('PagesService')->getResults($args, [['template', 'article']], $page);
	}

	public function getAjax($ajaxArgs = []) {
		return $this->getService('PagesService')->getAjax(['selector' => [['template', 'article']]]);
	}
}
