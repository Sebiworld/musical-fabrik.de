<?php

namespace ProcessWire;

class ContentFiles extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $this->files = array();
        if (isset($args['files'])) {
            $this->files = $args['files'];
        } elseif ($this->page->template->hasField('files') && !empty($this->page->files)) {
            $this->files = $this->page->files;
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

    public function getAjax($ajaxArgs = []) {
        $output = array(
            'type' => 'files',
            'depth' => $this->page->depth,
            'title' => $this->title,
            'hide_title' => $this->page->hide_title,
            'description' => $this->description,
            'classes' => $this->page->classes,
			'files' => $this->getAjaxOf($this->files)
        );

        return $output;
    }
}
