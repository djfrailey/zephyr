<?php

declare(strict_types=1);

namespace Zephyr\Controller\GitLab;

use function Amp\{all, resolve};
use Aerys\{
    Request,
    Response,
    function wait
};
use function Zephyr\Helpers\{
    decodeJsonBody,
    arrayGet
};
use function Zephyr\Model\User\{
    getUserByEmail,
    createUser
};
use function Zephyr\Model\Metric\{
    addMetric
};
use \Throwable;

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
    $values = yield from arrayGet($data, 'user_id', 'user_name', 'user_email', 'total_commits_count');

    $today = strtotime('today');

    // Attempt to resolve a user.
    try {
        $user = yield resolve(getUserByEmail($values['user_email']));
    } catch (Throwable $e) {
        $res->setStatus(500)->end(json_encode([
            'error' => $e->getMessage()
        ]));
    }

    // If the user does not already exist then attempt to create it.
    if ($user == false) {
        try {
            $user = yield resolve(createUser([
                'email_address' => $values['user_email'],
                'username' => $values['user_name'],
                'name' => $values['user_name'],
                'accounts' => [
                    [
                        'account_identifier' => $values['user_id'],
                        'account_type' => 'gitlab'
                    ]
                ]
            ]));
        } catch (Throwable $e) {
            $res->setStatus(500)->end(json_encode([
                'error' => $e->getMessage()
            ]));
        }
    }

    try {
        $metricAdded = yield resolve(addMetric([
            'user' => $user,
            'value' => $values['total_commits_count'],
            'type' => 'commit'
        ]));
    } catch (Throwable $e) {
        $res->setStatus(500)->end(json_encode([
            'error' => $e->getMessage()
        ]));
    }
    
    $res->setStatus(200)->end();
}

// function consumeIssueEvent(Response $res, array $data) : \Generator
// {
//     $objectAttributes = yield from arrayGet($data, 'object_attributes');
//     $values = yield from arrayGet($objectAttributes, 'id', 'assignee_id', 'author_id', 'project_id', 'action');
//     $authorFile = getUserFilenameById($values['author_id']);
//     $assigneeFile = getUserFilenameById($values['assignee_id']);

//     $authorPromise = getUserData($authorFile);
//     $assigneePromise = getUserData($assigneeFile);

//     list($author, $assignee) = yield all([$authorPromise, $assigneePromise]);

//     $week = strtotime('week');

//     if (isset($author[$week]['issues']) === false) {
//         $author[$week] = ['issues' => ['raised' => 0, 'resolved' => 0, 'open' => []]];
//     }

//     if (isset($assignee[$week]['issues']) === false) {
//         $assignee[$week] = ['issues' => ['raised' => 0, 'resolved' => 0, 'open' => []]];
//     }

//     if ($values['action'] === 'open') {
//         $author[$week]['issues']['raised'] += 1;
//         $assignee[$week]['issues']['open'][$values['id']] = $values['project_id'];   
//     }

//     if ($values['action'] === 'closed') {
//         if (isset($assignee[$week]['issues']['open'][$values['id']])) {
//             unset($assignee[$week]['issues']['open'][$values['id']]);
//             $assignee[$week]['issues']['resolved'] += 1;
//         }
//     }

//     $authorWrite = writeUserData($authorFile, $author);
//     $assigneeWrite = writeUserData($assigneeFile, $assignee);

//     yield all([$authorWrite, $assigneeWrite]);

//     $res->setStatus(200)->end();
// }