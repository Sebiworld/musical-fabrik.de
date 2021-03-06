<?php

namespace ProcessWire;

require_once wire('config')->paths->AppApi . 'vendor/autoload.php';
require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';

require_once __DIR__ . '/TwackAccess.class.php';
require_once __DIR__ . '/FileAccess.class.php';
require_once __DIR__ . '/AppApiTest.class.php';

$routes = [
	'v1' => [
		'test' => [
			['OPTIONS', '', ['GET']],
			['GET', '', AppApiTest::class, 'test', ['auth' => true]]
		]
	],
	'page' => [
		['OPTIONS', '{id:\d+}', ['GET', 'POST', 'UPDATE', 'DELETE']],
		['OPTIONS', '{path:.+}', ['GET', 'POST', 'UPDATE', 'DELETE']],
		['OPTIONS', '', ['GET', 'POST', 'UPDATE', 'DELETE']],
		['GET', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
		['GET', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
		['GET', '', TwackAccess::class, 'dashboardRequest'],
		['POST', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
		['POST', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
		['POST', '', TwackAccess::class, 'dashboardRequest'],
		['UPDATE', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
		['UPDATE', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
		['UPDATE', '', TwackAccess::class, 'dashboardRequest'],
		['DELETE', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
		['DELETE', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
		['DELETE', '', TwackAccess::class, 'dashboardRequest'],
	],
	'file' => [
		['OPTIONS', '{id:\d+}', ['GET']],
		['OPTIONS', '{path:.+}', ['GET']],
		['OPTIONS', '', ['GET']],
		['GET', '{id:\d+}', FileAccess::class, 'pageIDFileRequest'],
		['GET', '{path:.+}', FileAccess::class, 'pagePathFileRequest'],
		['GET', '', FileAccess::class, 'dashboardFileRequest']
	]
];
