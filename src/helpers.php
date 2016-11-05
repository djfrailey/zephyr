<?php

namespace Zephyr\Helpers;

use Aerys\Request;
use Amp\Deferred;
use Amp\Success;

function decodeJsonBody(Request $req)
{
    $body = $req->getBody(10 * 1024);

    $data = '';
    while (yield $body->valid()) {
        $data .= $body->consume();
    }

    return json_decode($data, true);
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