<?php namespace ProcessWire;

class SectionHeroImage extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		// Determine the ID of the one-page section:
		$this->sectionId = '';
		if ((string) $this->page->section_name) {
			$this->sectionId = (string) $this->page->section_name;
		}

		// The title can be set by $args or by field "title":
		if (isset($args['title'])) {
			$this->title = $args['title'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->title = $this->page->title;
		}

		$this->contents = '';
		if ($this->page->template->hasField('contents')) {
			$this->contents = $this->addComponent('ContentsComponent', ['directory' => '']);
		}

		// main image:
		$this->mainImage = '';
		if ($this->page->main_image && $this->page->main_image->url) {
			$this->mainImage = $this->page->main_image;
		}
	}
}
