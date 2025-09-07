<?php
namespace ProcessWire;

class ImagesBox extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$projectPage = $this->getGlobalParameter('projectPage');
		if (isset($args['projectPage']) && $args['projectPage'] instanceof Page && $args['projectPage']->id) {
			$projectPage = $args['projectPage'];
		}

		if (!($projectPage instanceof Page) || !$projectPage->id) {
			$projectPage = $this->getService('ProjectService')->getProjectPage($this->page);
		}

		if (!($projectPage instanceof Page) || !$projectPage->id) {
			throw new ComponentNotInitializedException('GeneralDataBox', $this->_('No project page was found.'));
		}

		$galleriesService = $this->getService('GalleriesService');
		$this->galleriesResponse = $galleriesService->getGalleries(['limit' => 1], $projectPage);

		$this->title = $this->_('Galleries');
		if (isset($args['title']) && !empty($args['title'])) {
			$this->title = str_replace(["\n", "\r"], '', $args['title']);
		}

		if ($this->galleriesResponse->items instanceof PageArray && count($this->galleriesResponse->items) > 0) {
			$this->galleriesPage   = $galleriesService->getGalleriesPage($projectPage);
			$this->sidebarGallery = $this->galleriesResponse->items->first();
			$this->addComponent('PageCard', [
				'directory' => '',
				'page' => $this->sidebarGallery,
				'autoplay' => true,
				'loop' => true
			]);
		}
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
			'galleries' => [],
			'galleries_count' => $this->galleriesResponse->totalNumber,
		];

		if ($this->galleriesPage instanceof Page && $this->galleriesPage->id && $this->galleriesPage->viewable()) {
			$output['galleries_page_url'] = $this->galleriesPage->url;
		}

		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax($ajaxArgs);
				if (empty($ajax) || !is_array($ajax)) {
					continue;
				}
				$output['galleries'][] = $ajax;
			}
		}

		return $output;
	}
}
