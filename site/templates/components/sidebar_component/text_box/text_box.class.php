<?php namespace ProcessWire;

class TextBox extends TwackKomponente {

	public function __construct($args) {
		parent::__construct($args);

		if (!isset($args['text']) || empty($args['text'])) {
			throw new ComponentNotInitializedException('TextBox', $this->_('Please pass the text to be displayed in the box.'));
		}

		$this->text = $args['text'];

		if (isset($args['title']) && !empty($args['title'])) {
			$this->title = str_replace(array("\n", "\r"), '', $args['title']);
		}
	}
}
