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
    ->expose('0.0.0.0', 8081)
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
	->use(function(Aerys\Request $req, Aerys\Response $res) {
	
		// Log the request body to a file so we can look at it later.
		$req->getBody()->watch(function($data) {			
			$filename = "./requests/" . uniqid() . "-" . time();
			\Amp\File\open($filename, 'w+')->when(function($e, $h) use ($data) {
				if ($e) {

				} else {
					$h->write($data)->when(function($e, $r) use ($h) {
						$h->close();
					});
				}
			});
		});	

	})
    ->use($routes);
