<?php

declare(strict_types=1);

namespace Zephyr\Controller\Dashboard;

const CRLF = "\r\n";

function CSV(array $data) : string {
    $data = array_map(function ($e) {
        return "$e";
    }, $data);

    return implode(',', $data);
}

function dashboard(\Aerys\Request $req, \Aerys\Response $res)
{
    static $metricEnum;
    static $metricLabelEnum;
    static $calls;

    if (isset($metricEnum) === false) {
        $metricEnum  = [
            'commit',
            'line_addition',
            'line_deletion',
            'line_total'
        ];

        $metricLabelEnum = [
            'User',
            'Commits',
            'Line Additions',
            'Line Deletions',
            'Line Additions/Deletions'
        ];
    }

    $promises = [];
    foreach($metricEnum as $metric) {
        $promises[$metric] = \Amp\resolve(
            \Zephyr\Model\Metric\getTodayMetricAggregate($metric)
        );
    }

    $resolved = yield \Amp\all($promises);

    $dataByUser = [];

    foreach($resolved as $key => $data) {
        foreach($data as $metrics) {
            if (isset($dataByUser[$metrics['email_address']]) === false) {
                $dataByUser[$metrics['email_address']] = [];
            }

            $dataByUser[$metrics['email_address']][$key] = round($metrics['sum'], 2);
        }
    }

    $rows = [];
    $rows[] = $metricLabelEnum;

    $res->setStatus(200)
    ->addHeader('Content-Type', 'text/csv')
    ->stream(CSV($metricLabelEnum) . CRLF);

    foreach($dataByUser as $user => $data) {
        $string = CSV([
            $user,
            $data['commit'],
            $data['line_addition'],
            $data['line_deletion'],
            $data['line_total']
        ]);

        $res->stream($string . CRLF);
        $res->flush();
    }
    $res->end();
}