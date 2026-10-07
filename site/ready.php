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

// Servers with $config->noindex (e.g. test servers) render every page as
// noindex, nofollow and leave it out of the sitemap. Only the rendered values
// change, the stored settings of the field stay as they are.
if ($wire->config->noindex === true) {
	$wire->addHookAfter('SeoMaestro::renderSeoDataValue', function (HookEvent $event) {
		$group = $event->arguments(0);
		$name = $event->arguments(1);

		if ($group === 'robots' && ($name === 'noIndex' || $name === 'noFollow')) {
			$event->return = 1;
		} elseif ($group === 'sitemap' && $name === 'include') {
			$event->return = 0;
		}
	});
}

// Performances have no page of their own in the frontend tree, so they are
// added to the sitemap of SeoMaestro with their frontend URL.
$wire->addHookAfter('SeoMaestro::sitemapItems', function (HookEvent $event) {
	$items = $event->return;
	$seoService = $event->wire('modules')->get('Twack')->getService('SeoService');
	foreach ($seoService->getPerformanceSitemapItems() as $item) {
		$items[] = $item;
	}
	$event->return = $items;
});

// The sitemap lists only public pages, by the same rule as the link preview:
// SeoMaestro itself would also list pages below an unpublished parent.
$wire->addHookAfter('SeoMaestro::sitemapAlwaysExclude', function (HookEvent $event) {
	$excluded = $event->return;
	$excluded->import($event->wire('modules')->get('Twack')->getService('SeoService')->getNotPublicSitemapPages());
	$event->return = $excluded;
});

// Link preview services run no JavaScript and only see the shell of the
// frontend. The web server sends their requests here with the frontend path
// in `path`, and they get the title, description and image of that page.
$wire->addHook('/link-preview/', function (HookEvent $event) {
	$service = $event->wire('modules')->get('Twack')->getService('LinkPreviewService');

	return $service->renderResponse($event->wire('input')->get('path'));
});
