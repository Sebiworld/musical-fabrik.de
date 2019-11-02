<?php

namespace ProcessWire;

class ContentArticles extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $contents = '';
        if (isset($args['typ']) && $args['typ'] == 'tiles') {
            $contents = $this->addComponent('ArticlesTiles', ['directory' => 'partials']);
        } else {
            $contents = $this->addComponent('ArticlesCarousel', ['directory' => 'partials']);
        }

        // Check if there is really outputable content available (HTML string not empty):
        $this->contentAvailable = false;
        if (!empty((string) $contents)) {
            $this->contentAvailable = true;
        }

        // The title can be set by $args or by field "title":
        $this->title = $this->_('News & Events');
        if (isset($args['title'])) {
            $this->title = $args['title'];
        } elseif ($this->page->template->hasField('title') && !empty($this->page->title)) {
            $this->title = $this->page->title;
        }
    }

    public function getAjax() {
        $output = array();

        if ($this->childComponents) {
            foreach ($this->childComponents as $component) {
                $ajax = $component->getAjax();
                if (empty($ajax)) {
                    continue;
                }
                $output = array_merge($output, $ajax);
            }
        }

        return $output;
    }
}
