<?php

namespace ProcessWire;

class ContentAudiofiles extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $this->audiofiles = array();
        if (isset($args['audiofiles'])) {
            $this->audiofiles = $args['audiofiles'];
        } elseif ($this->page->template->hasField('audiofiles') && !empty($this->page->audiofiles)) {
            $this->audiofiles = $this->page->audiofiles;
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

        $this->addScript('mediaplayer.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/mediaplayer.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
    }
}
