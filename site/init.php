<?php
namespace ProcessWire;

// The backend serves no HTML pages: the frontend is a separate app and gets
// page contents only through the API, which renders pages as JSON. Every
// other page request ends in the same 404 as an unknown URL, also for
// logged-in users. The hooks are added here (init) because ProcessWire
// resolves the requested page before the ready state.

// A page URL outside the admin resolves to no page, so ProcessWire answers it
// like an unknown URL. Without this, an existing page (also one a guest may
// not see) would answer a URL without trailing slash with a redirect and an
// unknown URL with 404, which tells whether a page exists. Requests for
// secured page files (`pagefileSecure`) keep their own access check, and
// URLs of the API and of path hooks match no page anyway.
$wire->addHookAfter('PagesRequest::getPage', function (HookEvent $event) {
	$page = $event->return;
	if (!($page instanceof Page) || !$page->id) {
		return;
	}

	$request = $event->object;
	if ($request->getFile()) {
		return;
	}
	if ($page->rootParent()->id === (int) $event->wire('config')->adminRootPageID) {
		return;
	}

	$event->return = $request->setPage($event->wire('pages')->newNullPage());
});

// Second line of defense for every page render: output is allowed for API
// requests (AppApi matches the request URL against its endpoint) and for
// Twack JSON output switched on by server code; request headers alone never
// allow it. The admin keeps working.
$renderOnlyForTheApi = function (HookEvent $event) {
	$page = $event->object;
	$options = $event->arguments(0);

	// $page->render('field_name') renders a single field, not the page.
	if (is_string($options) && $options !== '' && ctype_alnum(str_replace('_', '', $options))) {
		return;
	}
	if ($page->template->name === 'admin') {
		return;
	}

	$appApi = $event->wire('modules')->get('AppApi');
	if ($appApi instanceof AppApi && $appApi->isApiCall()) {
		return;
	}

	// JSON output that server code switched on with enableAjaxResponse()
	// (the page handler of the API, also when called in-process). Twack
	// also reports an ajax call for the client header X-Requested-With
	// ($config->ajax), so that case does not count.
	$twack = $event->wire('twack');
	if ($twack instanceof Twack && $twack->isTwackAjaxCall() && !$event->wire('config')->ajax) {
		return;
	}

	// ProcessPageView renders the 404 page after a Wire404Exception (status
	// 404 is already sent). Answer it with a short text instead of throwing
	// again, which would end in a 500.
	if ($page->id === (int) $event->wire('config')->http404PageID) {
		$event->replace = true;
		$event->cancelHooks = true;
		$event->return = '404 Not Found';
		return;
	}

	throw new Wire404Exception('Pages are only available through the API.');
};
// Priority 10: before other before-hooks with the default priority 100, e.g.
// the vote action of FieldtypeComments, which answers ajax requests itself.
// Page::render calls Page::renderPage (ProcessWire 3.0.253+), which is hooked
// as well in case it gets called directly: on the usual path the render hook
// already returned, threw or replaced the render, so renderPage either runs
// for an allowed request (the check passes again) or not at all.
$wire->addHookBefore('Page::render', $renderOnlyForTheApi, ['priority' => 10]);
$wire->addHookBefore('Page::renderPage', $renderOnlyForTheApi, ['priority' => 10]);
