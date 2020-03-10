<?php
namespace ProcessWire;

class TagsField extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$useField = 'tags';
		if ($this->page->template->hasField($useField) && $this->page->get($useField) instanceof PageArray) {
			$this->tags = $this->page->get($useField);
		}

		$this->searchPage = wire('pages')->find('template.name=search_page')->first();
	}

	public function getAjax($ajaxArgs = []){
		$output = array();

		if($this->tags instanceof PageArray){
			foreach($this->tags as $tag){
				$output[] = array(
					'id' => $tag->id,
					'title' => $tag->title,
					'name' => $tag->name
				);
			}
		}

		return $output;
	}
}
