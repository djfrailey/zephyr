<?php

namespace Zephyr\Helpers;

use Aerys\Request;
use Amp\Deferred;
use Amp\Success;
use Amp\Mysql\Pool;

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

function decodeJsonBody(Request $req)
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

function arrayGet(array $data, ...$keys)
{
    $found = [];

    foreach($keys as $search) {
        foreach($data as $k => $v) {

            if ($k === $search) {
                $found[$search] = $v;
            }

            yield new Success();
        }

        yield new Success();
    }

    if (count($found) === 1) {
        $found = reset($found);
    }
    
    return $found;
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

        $pool = new Pool("host=$host;user=$user;pass=\"$password\";db=$database");
    }

    return $pool;
}