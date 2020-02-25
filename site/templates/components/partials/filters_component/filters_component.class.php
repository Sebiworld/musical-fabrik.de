<?php

namespace ProcessWire;

class FiltersComponent extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        $this->tags = '';
        $this->q = '';

        if (isset($args['filters'])) {
            // Is a keyword filter set?
            if (isset($args['filters']['tags'])) {
                $this->tags = $args['filters']['tags'];
            }

            // Is something entered in the free text search?
            if (isset($args['filters']['q'])) {
                $this->q = $args['filters']['q'];
            }
        }

        // Add keyword selection:
        $this->addComponent('TagsFilter', ['directory' => 'partials', 'name' => 'tags', 'active' => $this->tags, 'show_count' => true]);
    }
}
