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

	public function isProjectPage($page = false){
		if(!($page instanceof Page)){
			$page = $this->projectPage;
		}
		return $page instanceof Page && substr($page->template->name, 0, 7) === 'project' && $page->template->name !== 'project_role' && $page->template->name !== 'project_roles_container' && $page->template->name !== 'projects_container';
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
