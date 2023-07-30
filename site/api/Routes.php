<?php
namespace ProcessWire;

require_once wire('config')->paths->AppApi . 'vendor/autoload.php';
require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';

require_once __DIR__ . '/AppApiTest.class.php';
require_once __DIR__ . '/GeneralApi.class.php';

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
	]
];
