<?php
namespace ProcessWire;

class SectionArticlesCarousel extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		// Determine the ID of the one-page section:
		$this->sectionId = '';
		if ((string) $this->page->section_name) {
			$this->sectionId = (string) $this->page->section_name;
		}

		// The title can be set by $args or by field "title":
		$this->title = $this->_('News');
		if (isset($args['title'])) {
			$this->title = $args['title'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->title = $this->page->title;
		}

		$this->addComponent('ArticlesCarousel', ['directory' => 'partials', 'name' => 'carousel']);

		if ($this->page->template->hasField('contents')) {
			$this->addComponent('ContentsComponent', ['directory' => '']);
		}
	}
}
