<?php
namespace ProcessWire;

class YoutubeVideo extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
		$this->addScript('youtube-video.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/youtube-video.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
	}

	public function getAjax(){
		$output = array(
			'type' => 'text',
			'video_id' => $this->page->kurztext
		);

		return $output;
	}
}
