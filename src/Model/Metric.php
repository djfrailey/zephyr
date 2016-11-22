<?php

namespace Zephyr\Model\Metric;

function addMetric(array $data)
{
    if (empty($data['user'])) {
        throw new \Exception("A user object must be supplied.");
    }

    if (empty($data['value'])) {
        throw new \Exception("A metric value must be supplied.");
    }

    if (empty($data['type'])) {
        throw new \Exception("A metric type must be suppleid.");
    }

    static $statement;

    if (isset($statement) === false) {
        $statement = yield \Zephyr\Helpers\connectionPool()->prepare("INSERT INTO metrics (email_address, metric_type, value, `date`) VALUES(:email, :type, :value, CURDATE())");
    }

    $statement->execute([
        'email' => $data['user']['email_address'],
        'type' => $data['type'],
        'value' => $data['value']
    ]);

    return true;
}

function getTodayMetricAggregate(string $type)
{
    static $statement;

    if (isset($statement) === false) {
        $statement = yield \Zephyr\Helpers\connectionPool()->prepare("SELECT email_address, SUM(`value`) AS `sum` FROM metrics WHERE `date`=CURDATE() AND metric_type=:type GROUP BY email_address ORDER BY `sum` DESC");
    }

    $set = yield $statement->execute(compact('type'));
    return (yield $set->fetchAll());
}