<?php

namespace Zephyr\Helpers;

use Aerys\Request;
use Amp\Deferred;
use Amp\Success;

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