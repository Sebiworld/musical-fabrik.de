<?php

namespace ProcessWire;

class ContentText extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);
    }

    public function getAjax($ajaxArgs = []) {
        $output = array(
            'type' => 'text',
            'depth' => $this->page->depth,
            'title' => $this->page->title,
            'hide_title' => $this->page->hide_title,
            'text' => $this->page->text,
            'classes' => $this->page->classes
        );

        return $output;
    }
}
