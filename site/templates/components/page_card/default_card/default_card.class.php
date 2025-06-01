<?php
namespace ProcessWire;

class DefaultCard extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->date = $this->page->created;
		if ($this->page->template->hasField('datetime_from')) {
			$this->date = $this->page->getUnformatted('datetime_from');
		}
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
		];

		$output['datetime_from'] = $this->date;
		$output['intro'] = $this->page->intro;

		$output['details_deactivated'] = !$this->page->template->hasField('no_details_view') || !$this->page->no_details_view;

		if ($output['details_deactivated']) {
			if ($this->page->template->hasField('btn_text')) {
				$output['btn_text'] = $this->page->btn_text;
			}
		} else {
			unset($output['url'], $output['httpUrl']);
		}

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

		if ($this->page->template->hasField('info_overlay') && $this->page->info_overlay) {
			$output['description'] = $this->page->info_overlay;
		}

		return $output;
	}
}
