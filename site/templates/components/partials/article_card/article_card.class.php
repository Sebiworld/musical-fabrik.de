<?php
namespace ProcessWire;

class ArticleCard extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
	}

	public function getAjax(){
		$output = $this->getAjaxOf($this->page);
		$output['datetime_from'] = $this->page->getUnformatted('datetime_from');
		$output['intro'] = $this->page->intro;

		if(wire('input')->get('htmlOutput')){
			$output['html'] = $this->renderView();
		}

		if($this->page->main_image){
			$output['main_image'] = $this->getAjaxOf($this->page->main_image->height(300));
		}

		if($this->page->color){
			$output['color'] = $this->page->color;
		}

		return $output;
	}
}
