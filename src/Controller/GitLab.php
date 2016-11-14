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

function consumeIssueEvent(Response $res, array $data) : \Generator
{
    $action = $data['object_attributes']['action'];
    $issuerId = $data['object_attributes']['author_id'];
    $assigneeId = $data['object_attribues']['assignee_id'];

    list($issuer, $assignee) = yield all([
        getUserByAccountId([
            'id' => $issuerId,
            'type' => 'gitlab'
        ]),
        getUserByAccountId([
            'id' => $assigneeId,
            'type' => 'gitlab'
        ])
    ]);

    if ($action === 'open') {

        try {
            yield all([
                addMetric([
                    'user' => $issuer,
                    'type' => 'authored_issue',
                    'value' => 1
                ]),
                addMetric([
                    'user' => $assignee,
                    'type' => 'open_issue',
                    'value' => 1
                ])
            ]);
        } catch (Throwable $e) {
            $res->setStatus(500)->end(json_encode([
                'error' => $e->getMessage()
            ]));
        }

    } else if ($action === 'closed') {

        try {
            yield resolve(addMetric([
                'user' => $assignee,
                'type' => 'resolved_issue',
                'value' => 1
            ]));
        } catch (Throwable $e) {
            $res->setStatus(500)->end(json_encode([
                'error' => $e->getMessage()
            ]));
        }

    }
    
    $res->setStatus(200)->end();
}