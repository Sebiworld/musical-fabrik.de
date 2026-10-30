<?php

namespace ProcessWire;

class ContentsComponent extends TwackComponent {
    public function __construct($args = array(), $page = null, $paths = null) {
        parent::__construct($args, $page, $paths);

        if (isset($args['useField']) && !empty($args['useField'])) {
            $this->useField = (string) $args['useField'];
        } elseif (!isset($this->useField) || empty($this->useField)) {
            $this->useField = 'contents';
        }

        $this->createFromField($this->useField);
    }

    /**
     * Accepts a field (RepeaterMatrix) and assembles the slider pages from the set contents.
     * @param Field $field
     */
    protected function createFromField($field) {
        if ($this->page->template->hasField($field)) {
            foreach ($this->page->get($field) as $contentElement) {
                // Assign names from the repeater matrix to component names:
                try {
                    // Search for the component in the folder of the content component:
                    $this->addComponent('content_' . $contentElement->type, [
                        'page'       => $contentElement,
                        'parameters' => $this->getArray()
                    ]);
                } catch (\Exception $e) {
                    try {
                        $this->addComponent('content_' . $contentElement->type, [
                            'page'       => $contentElement,
                            'location'   => array(),
                            'parameters' => $this->getArray()
                        ]);
                    } catch (\Exception $e) {
                    }
                }
            }
        }
    }

    public function getAjax($ajaxArgs = []) {
        $output = array(
            'contents' => []
        );

        if ($this->childComponents) {
            foreach ($this->childComponents as $component) {
                $ajax = $component->getAjax($ajaxArgs);
                if (empty($ajax)) {
                    continue;
                }

                $page                 = $component->getPage();
                $ajax['depth']        = $page->depth;
                $output['contents'][] = $ajax;
            }
        }

        return $output;
    }
}
