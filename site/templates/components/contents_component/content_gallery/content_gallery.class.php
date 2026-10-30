<?php
namespace ProcessWire;

class ContentGallery extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		$this->images = [];
		if (isset($args['images'])) {
			$this->images = $args['images'];
		} elseif ($this->page->template->hasField('images') && !empty($this->page->images)) {
			$this->images = $this->page->images;
		}

		// The title can be set by $args or by field "title":
		if (isset($args['title'])) {
			$this->title = $args['title'];
		} elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
			$this->title = $this->page->title;
		}

		if (isset($args['description'])) {
			$this->description = $args['description'];
		} elseif ($this->page->template->hasField('text') && !empty($this->page->text)) {
			$this->description = $this->page->text;
		}

		$this->type = 'masonry';
		if ($this->page->template->hasField('gallery_type')) {
			$types = [2 => 'slider', 3 => 'grid', 4 => 'featured_slider', 5 => 'panorama_slider'];
			$typeId = $this->page->gallery_type->id;
			if (is_int($typeId) && isset($types[$typeId])) {
				$this->type = $types[$typeId];
			}
		}
	}

	public function getAjax($ajaxArgs = []) {
		$output = [
			'type' => 'gallery',
			'id' => $this->page->id,
			'depth' => $this->page->depth,
			'title' => $this->title,
			'hide_title' => $this->page->hide_title,
			'text' => AppApi::replaceRootLinksInText($this->description),
			'classes' => $this->page->classes,
			'images' => $this->getAjaxOf($this->images),
			'gallery_type' => $this->type
		];

		return $output;
	}
}
