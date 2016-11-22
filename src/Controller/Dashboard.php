<?php

declare(strict_types=1);

namespace Zephyr\Controller\Dashboard;

function commits(\Aerys\Request $req, \Aerys\Response $response)
{
    $data = yield \Amp\resolve(
        \Zephyr\Model\Metric\getTodayMetricAggregate('commit')
    );

    $response->setStatus(200)
    ->addHeader('Content-Type', 'text/html')
    ->end(\Zephyr\Helpers\view('commit-table', compact('data')));
}