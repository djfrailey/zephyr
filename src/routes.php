<?php

use Aerys\Router;

$router = new Router();

$router
	->get('/dashboard/commits', 'Zephyr\Controller\Dashboard\commits')
    ->post('/gitlab/action', 'Zephyr\Controller\Gitlab\action');

return $router;
