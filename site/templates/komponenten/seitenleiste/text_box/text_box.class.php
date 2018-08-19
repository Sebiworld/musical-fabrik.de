<?php namespace ProcessWire;

class TextBox extends TwackKomponente {

	public function __construct($args) {
		parent::__construct($args);

		if (!isset($args['text']) || empty($args['text'])) {
			throw new ComponentNotInitialisedException('TextBox', 'Bitte übergeben Sie den Text, der in der Box angezeit werden soll.');
		}

		$this->text = $args['text'];
		
		if (isset($args['titel']) && !empty($args['titel'])) {
			$this->titel = str_replace(array("\n", "\r"), '', $args['titel']);
		}
	}
}
