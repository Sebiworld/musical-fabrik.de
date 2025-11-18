<?php
namespace ProcessWire;

class Alerts extends TwackComponent {
	public function __construct($args) {
		parent::__construct($args);

		if (!isset($args['useField']) || !is_string($args['useField']) || empty($args['useField'])) {
			$args['useField'] = 'alerts';
		}
		if (!wire('fields')->get($args['useField'])) {
			throw new ComponentNotInitializedException('Alerts', 'There is no field with the name "%1$s"!', $args['useField']);
		}

		$this->fieldName = $args['useField'];
	}

	public function getAjax($ajaxArgs = []) {
		$output = [];

		if ($this->page->template->hasField($this->fieldName)) {
			foreach ($this->page->get($this->fieldName) as $alert) {
				if (!$alert->id) {
					continue;
				}

				$output[] = [
					'id'		=> $alert->id,
					'title' => $alert->title,
					'text'  => $alert->text,
					'color'  => $alert->theme_color->value,
					'classes'	=> $alert->classes,
					'icon' => $alert->ionicon,
					'severity' => $alert->alert_severity->value,
					'type' => $alert->alert_type->value,
					'closable' => (bool) $alert->closable,
				];
			}
		}

		return $output;
	}
}
