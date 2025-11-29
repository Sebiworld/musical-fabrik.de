<?php
namespace ProcessWire;

class BreadcrumbsComponent extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->breadcrumbs  = $this->page->parents;
		$this->page->active = true;
		$this->breadcrumbs->add($this->page);

		$limit  = 50;
		$endstr = '&nbsp;…';

		foreach ($this->breadcrumbs as &$b) {
			$b->title_short = Twack::wordLimiter($b->title, $limit, $endstr);
		}
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
			'breadcrumbs' => []
		];

		foreach ($this->breadcrumbs as &$b) {
			$output['breadcrumbs'][] = [
				'id' => $b->id,
				'title' => $b->title,
				'url' => AppApi::getUrlRelativeToRoot($b->url),
				'httpUrl' => AppApi::getHttpUrlRelativeToRoot($b->httpUrl),
				'active' => !!$b->active,
				'viewable' => !!$b->viewable(),
			];
		}

		return $output;
	}
}
