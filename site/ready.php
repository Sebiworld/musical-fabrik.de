<?php
namespace ProcessWire;

$wire->addHookAfter('InputfieldPage::getSelectablePages', function($event) {
	if($event->object->hasField == 'portraits') {
		$projectpage = $event->arguments('page')->closest("template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container");
		if(!($projectpage instanceof Page) || !$projectpage->id){
			$projectpage = $event->pages->get('/');
		}
		$event->return = $projectpage->find("template.name=portrait, sort=title");
	}
});