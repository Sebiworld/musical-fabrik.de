<?php
namespace ProcessWire;

require_once wire('config')->paths->AppApi . 'vendor/autoload.php';
require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';

require_once __DIR__ . '/AppApiTest.class.php';
require_once __DIR__ . '/GeneralApi.class.php';
require_once __DIR__ . '/ConfigApi.class.php';
require_once __DIR__ . '/ProjectApi.class.php';
require_once __DIR__ . '/ProjectRolesApi.class.php';
require_once __DIR__ . '/PageListApi.class.php';

$routes = [
	'auth' => [
		['GET', '', GeneralApi::class, 'currentUser', [], [
			// documentation
			'summary' => 'Get the current user',
			'description' => 'Get the user from the current session.',
			'tags' => ['Authentication'],
			'security' => [
				['apiKey' => []],
				['bearerAuth' => []]
			],
			'parameters' => [],
			'responses' => [
				'200' => [
					'description' => 'Successfull operation',
					'content' => [
						'application/json' => [
							'schema' => [
								'required' => ['id', 'name', 'loggedIn', 'roles', 'permissions'],
								'type' => 'object',
								'properties' => [
									'id' => [
										'type' => 'integer',
										'format' => 'int64',
										'example' => 42
									],
									'name' => [
										'type' => 'string',
										'example' => 'sebi'
									],
									'loggedIn' => [
										'type' => 'boolean'
									],
									'nickname' => [
										'type' => 'string',
										'example' => 'Sebi'
									],
									'roles' => [
										'type' => 'array',
										'items' => [
											'required' => ['id', 'name'],
											'type' => 'object',
											'properties' => [
												'id' => [
													'type' => 'integer',
													'format' => 'int64',
													'example' => 42
												],
												'name' => [
													'type' => 'string',
													'example' => 'guest'
												],
												'title' => [
													'type' => 'string',
													'example' => 'Guest'
												],
												'description' => [
													'type' => 'string',
													'example' => 'This is the guest role.'
												],
											]
										]
									],
									'permissions' => [
										'type' => 'array',
										'items' => [
											'required' => ['id', 'name'],
											'type' => 'object',
											'properties' => [
												'id' => [
													'type' => 'integer',
													'format' => 'int64',
													'example' => 42
												],
												'name' => [
													'type' => 'string',
													'example' => 'page-view'
												],
												'title' => [
													'type' => 'string',
													'example' => 'Page: View'
												],
											]
										]
									]
								]
							]
						]
					]
				]
			]
		]],
	],

	'configuration' => [
		['OPTIONS', '', ['GET'], [], []],
		['GET', '', ConfigApi::class, 'getConfiguration'],
	],

	'menues' => [
		['OPTIONS', '', ['GET'], [], []],
		['GET', '', ConfigApi::class, 'getMenues'],
	],

	'projects' => [
		['OPTIONS', '', ['GET'], [], []],
		['GET', '', ProjectApi::class, 'getProjects'],
		['OPTIONS', '{id:\d+}', ['GET']],
		['GET', '{id:\d+}', ProjectApi::class, 'getProjectDetail']
	],

	'page-list' => [
		'items' => [
			['OPTIONS', '', ['GET'], [], []],
			['GET', '', PageListApi::class, 'getPageListItems'],
			['OPTIONS', '{id:\d+}', ['GET']],
			['GET', '{id:\d+}', PageListApi::class, 'getPageListItems']
		],
	],

	'project-roles' => [
		['OPTIONS', '', ['GET'], [], []],
		['GET', '', ProjectRolesApi::class, 'getProjectRoles'],
		['OPTIONS', '{id:\d+}', ['GET']],
		['GET', '{id:\d+}', ProjectRolesApi::class, 'getProjectRoles']
	],

	'project-portraits' => [
		['OPTIONS', '', ['GET'], [], []],
		['GET', '', ProjectRolesApi::class, 'getProjectPortraits']
	],

	'test' => [
		['OPTIONS', '', ['GET'], [], []],
		['GET', '', GeneralApi::class, 'errorTest']
	]
];
