<?php
namespace ProcessWire;

/**
 * Provides methods for reading galleries
 */
class GalleriesService extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);
		$this->projectPage = $this->getService('ProjectService')->getProjectPage();
	}

	public function getGalleriesPage($page = false) {
		$projectPage = $this->projectPage;
		if ($page instanceof Page && $page->id) {
			$projectPage = $this->getService('ProjectService')->getProjectPage($page);
		}

		if (!($projectPage instanceof Page) || !$projectPage->id) {
			return wire('pages')->get('/')->children('template.name=galleries_container')->first();
		}

		$results = $projectPage->find('template.name=galleries_container');

		if ($results->count <= 0) {
			return wire('pages')->get('/')->children('template.name=galleries_container')->first();
		}

		return $results->first();
	}

	/**
	 * Returns all galleries that can be output on this page.
	 * @return PageArray
	 */
	public function getGalleries($args = [], $basePage = false) {
		return $this->getService('PagesService')->getResults($args, [['template', 'gallery']], $basePage);
	}

	public function getAjax($ajaxArgs = []) {
		return $this->getService('PagesService')->getAjax(['selector' => [['template', 'gallery']]]);
	}
}
