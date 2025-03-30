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
		$output = [
			'datetime_from' => $this->date,
			'intro' => $this->page->intro
		];

		if ($this->page->template->hasField('gridImage') && $this->page->gridImage) {
			$output['main_image'] = $this->getAjaxOf($this->page->gridImage);
		} else if ($this->page->template->hasField('main_image') && $this->page->main_image) {
			$output['main_image'] = $this->getAjaxOf($this->page->main_image->height(300));
		}

		if ($this->page->template->hasField('card_image') && $this->page->card_image) {
			$output['card_image'] = $this->getAjaxOf($this->page->card_image);
		}

		if ($this->page->template->hasField('color') && $this->page->color) {
			$output['color'] = $this->page->color;
		}

		return $output;
	}
}
