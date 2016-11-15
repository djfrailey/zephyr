<?php

namespace Zephyr\Api\Gitlab;

const ENDPOINT_BASE = "https://git.clever.ly/api/v2";
const PRIVATE_ACCESS_TOKEN = "";

function getUserById(int $id)
{
    $url = ENDPOINT_BASE . '/users/' . $id;
    $client = new Amp\Artax\Client();
    $response = yield $client->request($url);

    if ($response->getStatus() >= 400) {
        throw new \Exception($response->getReason());
    }

    return json_decode($response->getBody(), true); 
}