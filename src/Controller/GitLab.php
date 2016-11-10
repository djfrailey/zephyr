<?php

declare(strict_types=1);

namespace Zephyr\Controller\GitLab;

use function Amp\all;
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
    getUserFilenameById,
    getUserData,
    writeUserData
};

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
    $values = yield from arrayGet($data, 'user_id','total_commits_count');

    $today = strtotime('today');
    $userFile = getUserFilenameById($data['user_id']);
    $userData = yield getUserData($userFile);

    $todaysCommits = !empty($userData[$today]['commits']) ? $userData[$today]['commits'] : 0;

    if (!isset($userData[$today]['commits'])) {
        $userData[$today] = ['commits' => 0];
    }

    $userData[$today]['commits'] += $values['total_commits_count'];
    yield writeUserData($userFile, $userData);
    
    $res->setStatus(200)->end();
}

function consumeIssueEvent(Response $res, array $data) : \Generator
{
    $objectAttributes = yield from arrayGet($data, 'object_attributes');
    $values = yield from arrayGet($objectAttributes, 'id', 'assignee_id', 'author_id', 'project_id', 'action');
    $authorFile = getUserFilenameById($values['author_id']);
    $assigneeFile = getUserFilenameById($values['assignee_id']);

    $authorPromise = getUserData($authorFile);
    $assigneePromise = getUserData($assigneeFile);

    list($author, $assignee) = yield all([$authorPromise, $assigneePromise]);

    $week = strtotime('week');

    if (isset($author[$week]['issues']) === false) {
        $author[$week] = ['issues' => ['raised' => 0, 'resolved' => 0, 'open' => []]];
    }

    if (isset($assignee[$week]['issues']) === false) {
        $assignee[$week] = ['issues' => ['raised' => 0, 'resolved' => 0, 'open' => []]];
    }

    if ($values['action'] === 'open') {
        $author[$week]['issues']['raised'] += 1;
        $assignee[$week]['issues']['open'][$values['id']] = $values['project_id'];   
    }

    if ($values['action'] === 'closed') {
        if (isset($assignee[$week]['issues']['open'][$values['id']])) {
            unset($assignee[$week]['issues']['open'][$values['id']]);
            $assignee[$week]['issues']['resolved'] += 1;
        }
    }

    $authorWrite = writeUserData($authorFile, $author);
    $assigneeWrite = writeUserData($assigneeFile, $assignee);

    yield all([$authorWrite, $assigneeWrite]);

    $res->setStatus(200)->end();
}