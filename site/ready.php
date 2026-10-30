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

// The sitemap file follows every change of a page: the change marks it as
// outdated, and the end of the next web request rewrites it (SitemapService).
$markSitemapOutdated = function (HookEvent $event) {
	$page = $event->arguments(0);
	$sitemap = $event->wire('modules')->get('Twack')->getService('SitemapService');
	if ($page instanceof Page && $sitemap->isRelevantChange($page)) {
		$sitemap->markOutdated();
	}
};
$wire->addHookAfter('Pages::saved', $markSitemapOutdated);
$wire->addHookAfter('Pages::deleted', $markSitemapOutdated);

// Runs after exit() as well, which ends API requests. SitemapService decides
// whether this request may write the sitemap: after sending the response
// (FastCGI), otherwise only in admin requests.
register_shutdown_function(function () use ($wire) {
	if (PHP_SAPI === 'cli') {
		return;
	}
	try {
		$wire->wire('modules')->get('Twack')->getService('SitemapService')->refreshAtEndOfRequest();
	} catch (\Throwable $e) {
		$wire->wire('log')->save('sitemap', 'Sitemap not refreshed: ' . $e->getMessage());
	}
});

// Link preview services run no JavaScript and only see the shell of the
// frontend. The web server sends their requests here with the frontend path
// in `path`, and they get the title, description and image of that page.
$wire->addHook('/link-preview/', function (HookEvent $event) {
	$service = $event->wire('modules')->get('Twack')->getService('LinkPreviewService');

	return $service->renderResponse($event->wire('input')->get('path'));
});

// "View" in the admin opens the page in the frontend: the backend renders no
// pages. Pages of the admin tree keep their own view, pages in the trash have
// no frontend counterpart and get no view link. Page::url and friends stay
// untouched, because API fields, sitemap and link previews depend on them.
$frontendViewService = function (HookEvent $event) {
	return $event->wire('modules')->get('Twack')->getService('SeoService');
};

$wire->addHookAfter('ProcessPageListActions::getActions', function (HookEvent $event) use ($frontendViewService) {
	$page = $event->arguments(0);
	$actions = $event->return;
	if (!is_array($actions) || !isset($actions['view']) || !($page instanceof Page)) {
		return;
	}

	$service = $frontendViewService($event);
	if ($service->isAdminPage($page)) {
		return;
	}
	if ($service->hasFrontendUrl($page)) {
		$actions['view']['url'] = $service->getFrontendUrl($page);
	} else {
		unset($actions['view']);
	}
	$event->return = $actions;
});

// The view tab of the page editor.
$wire->addHookBefore('ProcessPageEdit::buildFormView', function (HookEvent $event) use ($frontendViewService) {
	$page = $event->object->getPage();
	$service = $frontendViewService($event);
	if ($service->isAdminPage($page)) {
		return;
	}
	if ($service->hasFrontendUrl($page)) {
		$event->arguments(0, $service->getFrontendUrl($page));
	} else {
		$event->replace = true;
	}
});

// The entries of the view dropdown (panel, modal, new tab, exit and view).
$wire->addHookAfter('ProcessPageEdit::getViewActions', function (HookEvent $event) use ($frontendViewService) {
	$editor = $event->object;
	$page = $editor->getPage();
	$service = $frontendViewService($event);
	if ($service->isAdminPage($page) || $event->arguments(1)) {
		return;
	}
	if (!$service->hasFrontendUrl($page)) {
		$event->return = [];
		return;
	}

	$oldUrl = $editor->getViewUrl();
	$newUrl = $service->getFrontendUrl($page);
	$actions = [];
	foreach ($event->return as $name => $action) {
		// A draft page gets "?draft=1" appended to the view URL.
		$actions[$name] = str_replace(["'" . $oldUrl . "?draft=1'", "'" . $oldUrl . "'"], ["'" . $newUrl . "'", "'" . $newUrl . "'"], $action);
	}
	$event->return = $actions;
});

// "Save + View" redirects to the frontend, and is not offered without one.
$wire->addHookAfter('ProcessPageEdit::processSubmitAction', function (HookEvent $event) use ($frontendViewService) {
	if ($event->arguments(0) !== 'view') {
		return;
	}
	$editor = $event->object;
	$page = $editor->getPage();
	$service = $frontendViewService($event);
	if ($service->isAdminPage($page) || !$service->hasFrontendUrl($page)) {
		return;
	}
	$editor->setRedirectUrl($service->getFrontendUrl($page));
});

$wire->addHookAfter('ProcessPageEdit::getSubmitActions', function (HookEvent $event) use ($frontendViewService) {
	$page = $event->object->getPage();
	$actions = $event->return;
	$service = $frontendViewService($event);
	if (!isset($actions['view']) || $service->isAdminPage($page) || $service->hasFrontendUrl($page)) {
		return;
	}
	unset($actions['view']);
	$event->return = $actions;
});
