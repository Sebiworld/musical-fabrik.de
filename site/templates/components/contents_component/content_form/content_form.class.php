<?php

namespace ProcessWire;

class ContentForm extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        if (!$this->page->template->hasField('form')) {
            throw new ComponentNotInitializedException('ContentForm', 'No field for the container page has been defined on the one-page form.');
        }

        $containerPage = $this->page->get('form');
        if (!($containerPage instanceof Page) || !$containerPage->id) {
            throw new ComponentNotInitializedException('ContentForm', 'No valid container page was specified in the one-page form.');
        }

        // The title can be set by $args or by field "title":
        if (isset($args['title'])) {
            $this->title = $args['title'];
        } elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
            $this->title = $this->page->title;
        }

        $placeholders   = array();
        $forms          = $this->getGlobalComponent('forms');
        $this->form     = $forms->addComponent('FormTemplate', [
            'containerPage' => $containerPage,
            'placeholders'  => $placeholders,
            'page'          => $this->page
        ]);
    }
}
