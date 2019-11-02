<?php
namespace ProcessWire;

class ProjectService extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->projectPage = $this->page;
		if ($this->projectPage->template->name != 'project') {
			$this->projectPage = $this->page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
		}
		if (isset($args['projectPage']) && $args['projectPage'] instanceof Page && $args['projectPage']->id) {
			$this->projectPage = $args['projectPage'];
		}
		if (!($this->projectPage instanceof Page) || !$this->projectPage->id) {
			$this->projectPage = wire('pages')->get('/');
		}
	}

	public function getProjectPage(){
		return $this->projectPage;
	}

	public function getPortraitsContainer(){
		return wire('pages')->findOne('template.name=portraits_container, include=hidden, has_parent='.$this->projectPage->id);
	}

	public function getSeasonsContainer(){
		return wire('pages')->findOne('template.name=seasons_container, include=hidden, has_parent='.$this->projectPage->id);
	}

	public function getCastsContainer(){
		return wire('pages')->findOne('template.name=casts_container, include=hidden, has_parent='.$this->projectPage->id);
	}
}
