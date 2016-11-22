<?php

namespace Zephyr\Api\Gitlab;

function buildEndpointUrl(string $url)
{
    static $endpoint;

    if (isset($endpoint) === false) {
        $endpoint = rtrim(\Zephyr\Helpers\env('GITLAB_ENDPOINT'), '/');
    }

    return $endpoint . '/' . ltrim($url, '/');
}

function getUserById(int $id)
{
    $url = buildEndpointUrl("/users/$id");
    return (yield \Amp\resolve(doRequest($url)));
}

function getUserBySearch(string $search)
{
    $search = urlencode($search);
    $url = buildEndpointUrl("/users/?search=$search");
    return (yield \Amp\resolve(doRequest($url)));
}

function getUserByUsername(string $username)
{   
    $username = urlencode($username);
    $url = buildEndpointUrl("/users/?username=$username");
    return (yield \Amp\resolve(doRequest($url)));
}

function getCommit(int $projectId, string $hash)
{
    $url = buildEndpointUrl("projects/$projectId/repository/commits/$hash");
    return (yield \Amp\resolve(doRequest($url)));
}

function doRequest(string $url)
{
    $client = new \Amp\Artax\Client();
    $request = (new \Amp\Artax\Request)
                ->setUri($url)
                ->setHeader('PRIVATE-TOKEN', \Zephyr\Helpers\env('GITLAB_ACCESS_TOKEN'));

    $response = yield $client->request($request);

    if ($response->getStatus() >= 400) {
        throw new \Exception($response->getReason());
    }

    return json_decode($response->getBody(), true);
}