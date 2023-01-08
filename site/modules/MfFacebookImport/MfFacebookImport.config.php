<?php

$config = [
	'request_path' => [
		'type' => 'text',
		'label' => 'Request Path',
		'pattern' => '[a-z0-9-/]+',
		'minlength' => 1,
		'required' => false
	],
	'access_token' => [
		'type' => 'text',
		'label' => 'Access Token',
		'minlength' => 1,
		'required' => false
	]
];
