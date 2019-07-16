<?php

namespace ProcessWire;

require_once wire('config')->paths->RestApi . 'vendor/autoload.php';
require_once wire('config')->paths->RestApi . 'classes/RestApiHelper.php';

require_once __DIR__ . '/TwackAccess.class.php';

$routes = [
    'login' => [
        ['OPTIONS', ''],
        ['POST', '', Auth::class, 'login'],
    ],
    'access' => [
        ['OPTIONS', ''],
        ['POST', '', Auth::class, 'access'],
    ],
    'page' => [
        ['OPTIONS', ''], // this is needed for CORS Requests
		['GET', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
		['GET', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
		['POST', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
		['POST', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
		['UPDATE', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
		['UPDATE', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
		['DELETE', '{id:\d+}', TwackAccess::class, 'pageIDRequest'],
		['DELETE', '{path:.+}', TwackAccess::class, 'pagePathRequest'],
    ]
];
