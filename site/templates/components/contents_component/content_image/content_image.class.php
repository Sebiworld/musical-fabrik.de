<?php

namespace ProcessWire;

class ContentImage extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);
    }

    public function getAjax() {
        $output = array(
            'type' => 'image',
            'depth' => $this->page->depth,
            'title' => $this->page->title,
            'hide_title' => $this->page->hide_title,
            'caption' => $this->image->caption,
            'classes' => $this->page->classes,
            'image' => $this->getAjaxOf($this->page->image)
        );

        return $output;
    }
}
