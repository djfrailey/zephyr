<?php

declare(strict_types=1);

namespace Zephyr\Controller\GitLab;
use Aerys\Request;
use Aerys\Response;

const TYPE = 'gitlab';

function action(Request $req, Response $res)
{
    $data = yield from \Zephyr\Helpers\decodeJsonBody($req);
    $actionType = $data['object_kind'];

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
     // Attempt to resolve a user.
    $user = yield \Amp\resolve(
        \Zephyr\Model\User\getUserByEmail($data['user_email'])
    );

    // If the user does not already exist then attempt to create it.
    if ($user == false) {
        $user = yield \Amp\resolve(
            \Zephyr\Model\User\createUserPartial([
                'email_address' => $data['user_email'],
                'name' => $data['user_name'],
                'accounts' => [
                    [
                        'account_identifier' => $data['user_id'],
                        'account_type' => 'gitlab'
                    ]
                ]
            ], TYPE)
        );
    }

    yield \Amp\all([
        \Amp\resolve(
                \Zephyr\Model\Metric\addMetric(
                [
                    'user' => $user,
                    'value' => $data['total_commits_count'],
                    'type' => 'commit'
                ]
            )
        )
        ,
        \Amp\resolve(countUserFileChanges(
                $user,
                $data['project_id'],
                array_reduce($data['commits'], 
                    function(array $hashes, $current) {
                    $hashes[] = $current['id'];
                    return $hashes;
                }, 
                [])
            )
        )
    ]);

    $res->setStatus(200)->end();
}

function countUserFileChanges(array $user, int $projectId, array $hashes)
{
    $promises = [];
    foreach($hashes as $hash) {
        $promises[] = \Amp\resolve(
            \Zephyr\Api\Gitlab\getCommit($projectId, $hash)
        );
    }

    $resolved = yield \Amp\all($promises);

    error_log(json_encode($resolved));

    foreach($resolved as $commit) {

        if (!empty($commit['stats']['additions'])) {
            \Amp\resolve(
                \Zephyr\Model\Metric\addMetric(
                    [
                        'user' => $user,
                        'value' => $commit['stats']['additions'],
                        'type' => 'line_addition'
                    ]
                )
            );
        }

        if (!empty($commit['stats']['deletions'])) {
            \Amp\resolve(
                \Zephyr\Model\Metric\addMetric(
                    [
                        'user' => $user,
                        'value' => $commit['stats']['deletions'],
                        'type' => 'line_deletion'
                    ]
                )
            );
        }

        if (!empty($commit['stats']['total'])) {
            \Amp\resolve(
                \Zephyr\Model\Metric\addMetric(
                    [
                        'user' => $user,
                        'value' => $commit['stats']['total'],
                        'type' => 'line_total'
                    ]
                )
            );
        }
    }
}

function consumeIssueEvent(Response $res, array $data) : \Generator
{
    $action = $data['object_attributes']['action'];
    $issuerId = $data['object_attributes']['author_id'];
    $assigneeId = $data['object_attributes']['assignee_id'];

    list($issuer, $assignee) = yield \Amp\all([
        \Amp\resolve(
            \Zephyr\User\Model\getUserByAccountId($issuerId, TYPE)
        ),
        \Amp\resolve(
            \Zephyr\User\Model\getUserByAccountId($assigneeId, TYPE)
        )
    ]);

    $accountCreatePromises = [];
    if ($issuer == false) {
        $accountCreatePromises[] = \Zephyr\Model\User\createUserPartial([
            'accounts' => [
                [
                    'account_identifier' => $issuerId,
                    'account_type' => 'gitlab'
                ]
            ]
        ], TYPE);
    }

    if ($assignee == false) {
        $accountCreatePromises[] = \Zephyr\Model\User\createUserPartial([
            'accounts' => [
                [
                    'account_identifier' => $assigneeId,
                    'account_type' => 'gitlab'
                ]
            ]
        ], TYPE);
    }

    if ($accountCreatePromises) {
        list($issuer, $assignee) = yield \Amp\all($accountCreatePromises);
    }

    if ($action === 'open') {

        yield \Amp\all([
            \Amp\resolve(
                \Zephyr\Model\Metric\addMetric(
                [
                    'user' => $issuer,
                    'type' => 'authored_issue',
                    'value' => 1
                ]
            )),
            \Amp\resolve(
                \Zephyr\Model\Metric\addMetric(
                [
                    'user' => $assignee,
                    'type' => 'open_issue',
                    'value' => 1
                ]
            ))
        ]);

    } else if ($action === 'closed') {

        yield \Amp\resolve(
            \Zephyr\Model\Metric\addMetric(
            [
                'user' => $assignee,
                'type' => 'resolved_issue',
                'value' => 1
            ]
        ));
    }
    
    $res->setStatus(200)->end();
}
