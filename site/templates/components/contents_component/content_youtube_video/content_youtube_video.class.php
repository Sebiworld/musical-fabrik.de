<?php
namespace ProcessWire;

class ContentYoutubeVideo extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
		$this->addScript('content-youtube-video.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/content-youtube-video.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
	}

	public function getAjax(){
		$output = array(
			'type' => 'text',
			'video_id' => $this->page->short_text
		);

		return $output;
	}
}
