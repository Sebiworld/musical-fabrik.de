<?php
namespace ProcessWire;

class EventList extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);
		$this->eventsService = $this->getService('EventsService');
	}

	public function getAjax() {
		return $this->eventsService->getAjax();
	}
}
