<?php

namespace ProcessWire;

require_once wire('config')->paths->RestApi . 'vendor/autoload.php';
require_once wire('config')->paths->RestApi . 'classes/RestApiHelper.php';

require_once __DIR__ . '/TwackAccess.class.php';

$routes = [
    'current_user' => [
        ['OPTIONS', ''],
        ['GET', '', Auth::class, 'currentUser'],
    ],
    'page' => [
        ['OPTIONS', ''], // this is needed for CORS Requests
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
    ]
];
