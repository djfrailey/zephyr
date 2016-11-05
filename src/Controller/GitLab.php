<?php

declare(strict_types=1);

namespace Zephyr\Controller\GitLab;

use function Amp\resolve;
use Aerys\{
    Request,
    Response,
    function wait
};
use function Zephyr\Helpers\{
    decodeJsonBody,
    arrayGet
};
use function Zephyr\Model\File\{
    getUserData,
    writeUserData
};

const CONFIG = [
    'maxCommits' => 10
];

function action(Request $req, Response $res)
{
    $data = yield from decodeJsonBody($req);
    $actionType = yield from arrayGet($data, 'object_kind');

    $res->addHeader('Content-Type', 'application/json');
    
    $dispatched = yield from dispatch($actionType, $data, $res);

    if ($dispatched === false) {
        $res->setStatus(500)->end("GitLab event not bound.");
    }
}

function dispatch(string $action = "", array $data, Response $res)
{
    $dispatched = false;

    if ($action) {
        $action = ucwords($action);
        
        $method = "Zephyr\Controller\GitLab\consume${action}Event";

        if (function_exists($method)) {
            yield from $method($res, $data);
            $dispatched = true;
        } else {
            $dispatched = false;
        }
    }

    return $dispatched;
}

function consumePushEvent(Response $res, array $data) : \Generator
{ 
    $values = yield from arrayGet($data, 'user_id', 'project_id', 'user_avatar', 'total_commits_count');

    if (empty($values)) {
        $res->setStatus(400)->end("Received incorrect parameters for event.");
    }

    $today = strtotime('today');
    $userFile = "user-" . $values['user_id'];
    $userData = yield getUserData($userFile);

    $todaysCommits = !empty($userData[$today]['commits']) ? $userData[$today]['commits'] : 0;

    if ($todaysCommits < CONFIG['maxCommits']) {
        $userData[$today]['commits'] += $values['total_commits_count'];
    }

    $res->setStatus(200)->end(json_encode($userData));

    wait(writeUserData($userFile, $userData));
}
