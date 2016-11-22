<?php

declare(strict_types=1);

namespace Zephyr\Helpers;

/*
    UTILITY
*/

function getExecutionTime($reset = false)
{
    static $start;
    $time = 0;

    if (!$start || $reset) {
        $start = microtime(true);
    } else {
        $time = round((microtime(true) - $start), 4);
    }

    return $time;
}

function decodeJsonBody(\Aerys\Request $req)
{
    $logger = $req->getLocalVar('logger');

    $body = $req->getBody();

    $data = '';
    while (yield $body->valid()) {
        $string = $body->consume();

        $data .= $string;
    }

    $data = json_decode($data, true);
    
    return $data;
}

function env($key = null, $default = null)
{
    return $_ENV[$key] ?? $default;
}

function connectionPool()
{
    static $pool;

    if (isset($pool) === false) {

        $host = env("MYSQL_HOST");
        $user = env("MYSQL_USER");
        $password = env("MYSQL_PASSWORD");
        $database = env("MYSQL_DATABASE");

        $pool = new \Amp\Mysql\Pool("host=$host;user=$user;pass=$password;db=$database");
    }

    return $pool;
}

function view(string $filename, array $someVariableNameThatWillNeverCollide = array()) : string
{
    $filename = "./src/View/" . rtrim($filename, '.php') . '.php';
    
    $data = "";

    if (file_exists($filename)) {
        extract($someVariableNameThatWillNeverCollide, EXTR_OVERWRITE);
        ob_start();
        include($filename);
        $data = ob_get_clean();
    }

    return $data;
}