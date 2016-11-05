<?php

use Aerys\Bootable;
use Aerys\Host;

require_once(__DIR__ . '/vendor/autoload.php');

$routes = require_once('src/routes.php');

(new Host)
    ->expose('127.0.0.1', 8080)
    ->name('localhost')
    ->use($routes);
