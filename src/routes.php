<?php

use Aerys\Router;

$router = new Router();

$router
	->get('/dashboard', 'Zephyr\Controller\Dashboard\dashboard')
    ->post('/gitlab/action', 'Zephyr\Controller\Gitlab\action');

return $router;
