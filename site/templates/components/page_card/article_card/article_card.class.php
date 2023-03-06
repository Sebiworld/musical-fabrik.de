<?php
namespace ProcessWire;

class ArticleCard extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->date = $this->page->created;
		if ($this->page->template->hasField('datetime_from')) {
			$this->date = $this->page->getUnformatted('datetime_from');
		}
	}

	public function getAjax($ajaxArgs = []) {
		$output = $this->getAjaxOf($this->page);
		$output['datetime_from'] = $this->date;
		$output['intro'] = $this->page->intro;

		if (wire('input')->get('htmlOutput')) {
			$output['html'] = $this->renderView();
		}

		if ($this->page->main_image) {
			$output['main_image'] = $this->getAjaxOf($this->page->main_image->height(300));
		}

		if ($this->page->color) {
			$output['color'] = $this->page->color;
		}

		$output['hash'] = md5(serialize($output));
		$output['external_type'] = $this->page->external_type;
		if ($this->page->external_link) {
			$output['external_link'] = $this->page->external_link;
		}

		return $output;
	}
}
