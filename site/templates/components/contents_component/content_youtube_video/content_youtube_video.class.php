<?php

namespace ProcessWire;

class ContentYoutubeVideo extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $this->videoWidth = 560;
        if(!empty($this->page->width) && $this->page->width >= 100){
            $this->videoWidth = $this->page->width;
        }

        $this->videoHeight = 315;
        if(!empty($this->page->height) && $this->page->height >= 100){
            $this->videoHeight = $this->page->height;
        }

        $this->addScript('content-video.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/content-video.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
    }

    public function getAjax($ajaxArgs = []) {
        $output = array(
            'type'       => 'youtube-video',
            'depth'      => $this->page->depth,
            'title'      => $this->title,
            'hide_title' => $this->page->hide_title,
            'classes'    => $this->page->classes,
            'video_id'   => $this->page->short_text
        );

        return $output;
    }
}
