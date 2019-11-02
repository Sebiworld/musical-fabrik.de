<?php
namespace ProcessWire;

class SidebarComponent extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		if (isset($args['classes']) && is_array($args['classes'])) {
			$this->classes = $args['classes'];
		}
	}
}
