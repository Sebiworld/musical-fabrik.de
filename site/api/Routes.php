<?php
namespace ProcessWire;

require_once wire('config')->paths->AppApi . 'vendor/autoload.php';
require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';

require_once __DIR__ . '/AppApiTest.class.php';
require_once __DIR__ . '/GeneralApi.class.php';

$routes = [
	'auth' => [
		['GET', '', GeneralApi::class, 'currentUser']
	]
];
