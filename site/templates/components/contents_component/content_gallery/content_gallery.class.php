<?php
namespace ProcessWire;

class ContentGallery extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->images = array();
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

		$this->addScript('content-gallery.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/content-gallery.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));

		$this->type = 'masonry';
		if ($this->page->template->hasField('gallery_type') && $this->page->gallery_type->id === 2) {
			// Slider View
			$this->type = 'slider';
			$this->setView('ContentGallerySlider');

			$this->sliderAlign = 'center';
			if (isset($args['sliderAlign'])) {
				$this->sliderAlign = $args['sliderAlign'];
			} elseif (isset($this->section) && $this->section) {
				// In sections the slides should be left-justified.
				$this->sliderAlign = 'left';
			}
		} elseif ($this->page->template->hasField('gallery_type') && $this->page->gallery_type->id === 3) {
			// Grid view
			$this->type = 'grid';
			$this->addScript('masonry.js', array(
				'path'     => wire('config')->urls->templates . 'assets/js/',
				'absolute' => true
			));
			$this->addScript('legacy/masonry.js', array(
				'path'     => wire('config')->urls->templates . 'assets/js/',
				'absolute' => true
			));
			$this->setView('ContentGalleryGrid');
		} else {
			// Standard: Masonry-View
			$this->setView('ContentGalleryMasonry');
			$this->addScript('masonry.js', array(
				'path'     => wire('config')->urls->templates . 'assets/js/',
				'absolute' => true
			));
			$this->addScript('legacy/masonry.js', array(
				'path'     => wire('config')->urls->templates . 'assets/js/',
				'absolute' => true
			));
		}
	}

	public function getAjax($ajaxArgs = []) {
        $output = array(
            'type' => 'gallery',
            'depth' => $this->page->depth,
            'title' => $this->title,
            'hide_title' => $this->page->hide_title,
            'description' => $this->description,
            'classes' => $this->page->classes,
			'images' => $this->getAjaxOf($this->images),
			'gallery_type' => $this->type
        );

        return $output;
    }
}
