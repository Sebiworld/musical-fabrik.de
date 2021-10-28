<?php

namespace ProcessWire;

require_once wire('config')->paths->AppApi . 'vendor/autoload.php';
require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';

require_once __DIR__ . '/AppApiTest.class.php';

$routes = [
	'v1' => [
		'test' => [
			['OPTIONS', '', ['GET', 'POST']],
			['GET', '', AppApiTest::class, 'test', ['auth' => true]],
			['POST', '', AppApiTest::class, 'test', ['auth' => true]]
		]
	]
];
