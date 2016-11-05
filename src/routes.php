<?php

use Aerys\Router;

$router = new Router();

$router
    ->post('/gitlab/action', 'Zephyr\Controller\Gitlab\action');

return $router;
