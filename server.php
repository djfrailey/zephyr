<?php

use Aerys\Bootable;
use Aerys\Host;

require_once(__DIR__ . '/vendor/autoload.php');

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required([
	"MYSQL_HOST",
	"MYSQL_USER",
	"MYSQL_PASSWORD",
	"MYSQL_DATABASE"
]);

$routes = require_once('src/routes.php');

(new Host)
    ->expose('127.0.0.1', 80)
	->name('localhost')
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
