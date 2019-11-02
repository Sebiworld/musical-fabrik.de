<?php

namespace ProcessWire;

class FilesBox extends TwackComponent {
    public function __construct($args) {
        parent::__construct($args);

        if (!isset($args['useField']) || empty($args['useField'])) {
            throw new ComponentNotInitializedException(
                'FilesBox',
                $this->_('Please enter the name of the file field from which the download files are to be taken.')
            );
        }

        $this->dateien = $this->page->get($args['useField']);

        if (isset($args['title']) && !empty($args['title'])) {
            $this->title = str_replace(array("\n", "\r"), '', $args['title']);
        }
    }
}
