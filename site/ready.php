<?php
namespace ProcessWire;

$wire->addHookAfter('InputfieldPage::getSelectablePages', function($event) {
	if($event->object->hasField == 'portraits') {
		$projektseite = $event->arguments('page')->closest("template.name^=projekt");
		if(!($projektseite instanceof Page) || !$projektseite->id){
			$projektseite = $event->pages->get('/');
		}
		$event->return = $projektseite->find("template.name=portrait, sort=title");
	}
});