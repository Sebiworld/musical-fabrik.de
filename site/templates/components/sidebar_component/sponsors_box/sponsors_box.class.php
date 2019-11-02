<?php
namespace ProcessWire;

class SponsorsBox extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$projectPage = $this->getGlobalParameter('projectPage');
		if (isset($args['projectPage']) && $args['projectPage'] instanceof Page && $args['projectPage']->id) {
			$projectPage = $args['projectPage'];
		}
		if (!($projectPage instanceof Page) || !$projectPage->id) {
			throw new ComponentNotInitializedException('SponsorsBox', $this->_('No project page was found. '));
		}

		$this->title = $this->_('Sponsors');
		if (isset($args['title']) && !empty($args['title'])) {
			$this->title = str_replace(array("\n", "\r"), '', $args['title']);
		}

		$useField = 'foerderer';
		if (isset($args['useField']) && !empty($args['useField'])) {
			$useField = $args['useField'];
		}

		if (!$projectPage->template->hasField($useField)) {
			throw new ComponentNotInitializedException('SponsorsBox', sprintf($this->_('The required field was not found on the project page: "%1$s"'), $useField));
		}

		$sponsors = array();
		foreach ($projectPage->get($useField)->sort('name') as $sponsor) {
			$sponsors[] = $sponsor->title;
		}
		$this->sponsors = $sponsors;
	}
}
