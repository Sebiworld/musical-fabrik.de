<?php
namespace ProcessWire;

class ConfigurationService extends TwackComponent {

	protected $configPage;

	public function __construct($args) {
		parent::__construct($args);
		$this->configPage = wire('pages')->get('template.name=configuration');
	}

	public function getConfigurationPage() {
		return $this->configPage;
	}
}
