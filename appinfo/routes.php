<?php

return [
	'resources' => [
		'list' => ['url' => '/lists'],
		'list_api' => ['url' => '/api/0.1/lists']
	],
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'list_api#preflighted_cors', 'url' => '/api/0.1/{path}',
			'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']]
	]
];
