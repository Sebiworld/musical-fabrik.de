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

			$this->placeholders = [];
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
		abstract public function getFieldHtml(Field $field, Page $page, $evaluationResponse = []);

		public function getFieldAjax(Field $field, Page $page, $evaluationResponse = []) {
			$output = [
				'name' => $field->name,
				'id' => $this->idService->getID($field->name),
				// 'label' => $field->label,
				// 'description' => $this->replacePlaceholders($field->description),
				// 'notes' => $this->replacePlaceholders($field->notes),
				// 'size' => $field->size,
				// 'minlength' => $field->minlength,
				// 'maxlength' => $field->maxlength,
				'required' => !!$field->required && empty($field->requiredIf),
				'errors' => [],
				'successes' => []
			];

			$icon = $field->getInputfield($page)->icon;
			if ($icon) {
				$output['icon'] = $icon;
			}

			// Write text-values if not empty:
			foreach (['label', 'description', 'notes'] as $key) {
				$value = $field->get($key);
				if (!empty($value)) {
					$output[$key] = $value;
				}
			}

			// Write number-values if not empty:
			foreach (['rows', 'columnWidth', 'size', 'minlength', 'maxlength'] as $key) {
				$value = $field->get($key);
				if (is_numeric($value)) {
					$output[$key] = $value;
				}
			}

			$currentValue = null;
			if (!empty($evaluationResponse['fields'][$field->name]) && is_array($evaluationResponse['fields'][$field->name])) {
				if (isset($evaluationResponse['fields'][$field->name]['currentValue'])) {
					$currentValue = $evaluationResponse['fields'][$field->name]['currentValue'];
				}

				if (!empty($evaluationResponse['fields'][$field->name]['error']) && is_array($evaluationResponse['fields'][$field->name]['error'])) {
					$output['invalid'] = true;
					$output['errors'][] = $evaluationResponse['fields'][$field->name]['error'];
				} elseif (!empty($evaluationResponse['fields'][$field->name]['success']) && is_array($evaluationResponse['fields'][$field->name]['success'])) {
					$output['invalid'] = false;
					$output['successes'][] = $evaluationResponse['fields'][$field->name]['success'];
				}
			}

			if ($field->name === 'antispam_code') {
				$output['required'] = true;
				$output['type'] = 'antispam_code';
				$output['code'] = $this->replacePlaceholders($page->get($field->name));

			} elseif ($field->type instanceof \FieldtypeRuntimeMarkup) {
				$output['type'] = 'markup';
				$output['value'] = AppApi::replaceRootLinksInText($this->replacePlaceholders($page->get($field->name)));

			} elseif ($field->type instanceof FieldtypeText || $field->type instanceof FieldtypeFloat || $field->type instanceof FieldtypeInteger) {
				if (!empty($currentValue)) {
					$output['value'] = $currentValue;
				}

				if ($field->type instanceof FieldtypeTextarea) {
					$output['type'] = 'textarea';
				} elseif ($field->type instanceof FieldtypeEmail) {
					$output['type'] = 'email';
				} elseif ($field->inputType == 'number') {
					$output['type'] = 'number';
					if ($field->min && is_integer($field->min)) {
						$output['min'] = $field->min;
					}
					if ($field->max && is_integer($field->max)) {
						$output['max'] = $field->max;
					}
				} else {
					$output['type'] = 'text';
				}
			} elseif ($field->type instanceof FieldtypeCheckbox) {
				$output['type'] = 'checkbox';

				if (!empty($currentValue)) {
					$output['value'] = !!$currentValue;
				}
			} elseif ($field->type instanceof FieldtypeOptions) {
				$output['type'] = 'options';
				$output['isMultiselect'] = $field->getInputfield($page) instanceof InputfieldSelectMultiple;

				if ($output['isMultiselect']) {
					// $output['name'] .= '[]';
				}

				$output['options'] = [];
				foreach ($field->type->getOptions($field) as $option) {
					$output['options'][] = [
						'id' => $this->idService->getID($output['id'] . '-' . $option->id),
						'value' => $option->id,
						'checked' => !empty($currentValue) && is_array($currentValue) && in_array($option->id, $currentValue),
						'title' => $option->title
					];
				}
			} elseif ($field->type instanceof FieldtypeFieldsetClose) {
				$output['type'] = 'fieldset_close';
			} elseif ($field->type instanceof FieldtypeFieldsetOpen) {
				$output['type'] = 'fieldset_open';
			}

			return $output;
		}

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
