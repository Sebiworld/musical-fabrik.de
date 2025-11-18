<?php
namespace ProcessWire;

class SponsorsBox extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->sponsors = [];
		$this->sponsorObjects = [];

		try {
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


			$this->title = $this->_('Sponsors');
			if (isset($args['title']) && !empty($args['title'])) {
				$this->title = str_replace(["\n", "\r"], '', $args['title']);
			}

			$useField = 'foerderer';
			if (isset($args['useField']) && !empty($args['useField'])) {
				$useField = $args['useField'];
			}

			if (!$projectPage->template->hasField($useField)) {
				throw new ComponentNotInitializedException('SponsorsBox', sprintf('The required field was not found on the project page: "%1$s"', $useField));
			}

			$sponsors = [];
			$sponsorObjects = [];
			foreach ($projectPage->get($useField)->sort('name') as $sponsor) {
				$sponsors[] = $sponsor->title;
				$sponsorObjects[] = $sponsor;
			}

			$this->sponsors = $sponsors;
			$this->sponsorObjects = $sponsorObjects;
		} catch (\Throwable $e) {
		}
	}

	public function getAjax($ajaxArgs = []) {
		return array_map(function ($v) {
			return [
				'id' => $v->id,
				'title' => $v->title,
				// 'main_image' => AppApi::getAjaxOf($v->main_image)
			];
		}, $this->sponsorObjects);
	}
}
