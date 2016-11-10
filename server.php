<?php

use Aerys\Bootable;
use Aerys\Host;

require_once(__DIR__ . '/vendor/autoload.php');

$routes = require_once('src/routes.php');

(new Host)
    ->expose('127.0.0.1', 8080)
	->name('localhost')
    ->name('zephyr.dev')
    ->use(new class implements Bootable {
		private $logger;

		function boot(Aerys\Server $server, Aerys\Logger $logger) {
			$this->logger = $logger;
		}

		function __invoke(Aerys\Request $req, Aerys\Response $res) {
			$req->setLocalVar('logger', $this->logger);
		}
	})
    ->use($routes);
