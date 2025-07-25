<?php
namespace ProcessWire;

$wire->addHookAfter('InputfieldPage::getSelectablePages', function ($event) {
	if ($event->object->hasField == 'portraits' || $event->object->hasField == 'portrait') {
		$currentPage = $event->arguments('page');
		if ($currentPage instanceof RepeaterPage) {
			// Find Repeater parent
			$currentPage = $currentPage->getForPage();
		}

		$projectpage = $currentPage->closest('template.name=project|home');
		if (!($projectpage instanceof Page) || !$projectpage->id) {
			$projectpage = $event->pages->get('/');
		}
		$event->return = $projectpage->find('template.name=portrait, sort=title');
	} else if ($event->object->hasField == 'casts' || $event->object->hasField == 'cast') {
		$currentPage = $event->arguments('page');
		if ($currentPage instanceof RepeaterPage) {
			// Find Repeater parent
			$currentPage = $currentPage->getForPage();
		}

		$projectpage = $currentPage->closest('template.name=project|home');
		if (!($projectpage instanceof Page) || !$projectpage->id) {
			$projectpage = $event->pages->get('/');
		}
		$event->return = $projectpage->find('template.name=cast');
	} else if ($event->object->hasField == 'seasons') {
		$currentPage = $event->arguments('page');
		if ($currentPage instanceof RepeaterPage) {
			// Find Repeater parent
			$currentPage = $currentPage->getForPage();
		}

		$projectpage = $currentPage->closest('template.name=project|home');
		if (!($projectpage instanceof Page) || !$projectpage->id) {
			$projectpage = $event->pages->get('/');
		}
		$event->return = $projectpage->find('template.name=season');
	}
});


// Add the brand name after the title.
$wire->addHookAfter('SeoMaestro::renderSeoDataValue', function (HookEvent $event) {
	$group = $event->arguments(0);
	$name = $event->arguments(1);
	$value = $event->arguments(2);

	// Insert default values if empty:
	if (empty($value)) {
		if ($name === 'image') {
			$value = '/site/templates/assets/static_img/mf_hero.jpg';
			$event->return = $value;
		} elseif ($group === 'meta' && $name === 'description') {
			$value = 'Der Musical-Fabrik e.V. ist ein gemeinnütziger Verein, der generations-, interessen- und grenzübergreifend Menschen die Gelegenheit gibt, ihr Können auszuleben.';
			$event->return = $value;
		}
	}

	if ($group === 'meta' && $name === 'title') {
		$event->return = htmlspecialchars(strip_tags($value));
	} elseif ($name === 'description') {
		$event->return = htmlspecialchars(trim(str_replace('&nbsp;', ' ', strip_tags($value))));
	}
});
