<?php

use Aerys\Router;

$router = new Router();

$router
	->get('/', function($request, $response) {
		$response->end('<h1>Hi</h1>');
	})
	->get('/test', function($request, $response) {
		$response->end('<h1>Test</h1>');
	})
    ->post('/gitlab/action', 'Zephyr\Controller\Gitlab\action');

return $router;
