<?php
namespace ProcessWire;

class GalleryCard extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->images = $this->getImagesFromGallery();

		$this->autoplay = false;
		$this->hoverAutoplay = false;
		if(isset($args['autoplay']) && $args['autoplay']){
			$this->autoplay = $args['autoplay'];
		}else if(isset($args['hoverAutoplay']) && $args['hoverAutoplay']){
			$this->hoverAutoplay = $args['hoverAutoplay'];
		}else if(!isset($args['hoverAutoplay'])){
			$this->hoverAutoplay = true;
		}

		$this->loop = false;
		if(isset($args['loop']) && $args['loop']){
			$this->loop = true;
		}

		$this->addScript('swiper.js', array(
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		));
		$this->addScript('legacy/swiper.js', array(
			'path'     => wire('config')->urls->templates . 'assets/js/',
			'absolute' => true
		));
	}

	protected function getImagesFromGallery(){
		$images = new WireArray();

		if(!$this->page->template->hasField('contents')){
			return $images;
		}

		// Extract all images from the contentblocks:
		foreach($this->page->contents as $contentblock){
			if($contentblock->template->hasField('image')){
				$images->add($contentblock->image);
			}
			if($contentblock->template->hasField('images') && !empty($contentblock->images) && $contentblock->images->count > 0){
				$images->import($contentblock->images);
			}
		}

		return $images->slice(0, 10);
	}

	public function getAjax($ajaxArgs = []){
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

		$output['hash'] = md5(serialize($output));

		return $output;
	}
}
