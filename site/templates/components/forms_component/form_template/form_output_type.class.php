<?php

namespace ProcessWire;

/*
 * Specifies what a FormOutputType must look like:
 * (This way other output variants can be defined instead of bootstrap)
 */
if (!class_exists('Processwire\FormOutputType')) {
    abstract class FormOutputType extends TwackComponent {
        protected $idService;
        protected $placeholders;

        public function __construct($args) {
            parent::__construct($args);
            $this->idService = $this->getService('IdService');

            $this->placeholders = array();
            if (isset($args['placeholders']) && is_array($args['placeholders'])) {
                $this->placeholders = $args['placeholders'];
            }
        }

        /**
         * Returns the HTML string for the output of a single field.
         * @param  Field  $feld
         * @param  Page   $page
         * @return string
         */
        abstract public function getFieldHtml(Field $feld, Page $page, $evaluationResponse = []);

        /**
         * Searches a string for {{placeholders}}, and replaces them if matches have been defined in $args["placeholders"].
         * @param  string $input
         * @return string
         */
        protected function replacePlaceholders($input) {
            if (!is_string($input)) {
                $input .= '';
            }
            foreach ($this->placeholders as $key => $value) {
                $input = str_replace('{{ ' . $key . ' }}', $value, $input);
                $input = str_replace('{{' . $key . '}}', $value, $input);
            }
            $input = preg_replace("/\{\{([^}]+)\}\}/", '', $input);
            return $input;
        }

        /**
         * Checks whether a string has placeholders.
         * @param  string $input
         * @return boolean
         */
        protected function hasPlaceholder($input) {
            return !!strstr($input, '{{') && !!strstr($input, '}}');
        }

        /**
         * Converts an attribute array into a string that can be used as an HTML attribute.
         * @param  array $attributes
         * @return string
         */
        public function getAttributeString($attributes) {
            if (!is_array($attributes)) {
                return '';
            }

            $output = ' ';
            foreach ($attributes as $key => $value) {
                $output .= $key . '="' . $value . '" ';
            }
            return $output;
        }
    }
}
