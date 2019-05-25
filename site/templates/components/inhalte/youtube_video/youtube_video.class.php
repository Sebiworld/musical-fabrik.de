<?php
namespace ProcessWire;

class YoutubeVideo extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
		$this->addScript(wire('config')->urls->templates . 'assets/js/youtube-video.min.js', true, true);
	}

	public function getAjax(){
		$output = array(
			'type' => 'text',
			'video_id' => $this->page->kurztext
		);

		return $output;
	}
}
