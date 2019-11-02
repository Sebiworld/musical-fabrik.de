<?php

namespace ProcessWire;

class SectionCustomHero extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $this->imageService = $this->getService('ImageService');

        // Determine the ID of the one-page section:
        $this->sectionId = '';
        if ((string) $this->page->section_name) {
            $this->sectionId = (string) $this->page->section_name;
        }

        // The title can be set by $args or by field "title":
        if (isset($args['title'])) {
            $this->title = $args['title'];
        } elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
            $this->title = $this->page->title;
        }

        $this->addScript('custom-hero.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/custom-hero.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
    }
}
