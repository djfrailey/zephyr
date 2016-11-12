<?php

namespace Zephyr\Model\User;

use function Zephyr\Helpers\connectionPool;
use function Zephyr\Model\Account\createUserAccount;
use Amp\{Deferred, Promise, Failure, Success, function resolve};
use \stdClass;
use \Exception;

function createUser(array $data)
{
    static $statement;
    $result = false;

    if (isset($data['email_address']) === false) {
        throw new Exception("Email address must be provided when creating a new user.");
    } else if (isset($data['username']) === false) {
        throw new Exception("Username must be provided when creating a new user.");
    } else if (isset($data['name']) === false) {
        throw new Exception("Name must be provided when creating a new user.");
    } else {

        if (isset($statement) === false) {
            $statement = yield connectionPool()->prepare("INSERT INTO users (email_address, username, name) VALUES (:email_address, :username, :name)");
        }

        yield $statement->execute($data);

        if (isset($data['accounts']) === true) {
            foreach($data['accounts'] as $account) {
                $account['user_email_address'] = $data['email_address'];
                yield resolve(createUserAccount($account));
            }
        }

        unset($data['accounts']);
        $result = $data;
    }

    return $result;
}

function getUserByEmail(string $email)
{
    static $statement;

    if (isset($statement) === false) {
        $statement = yield connectionPool()->prepare("SELECT * FROM users WHERE email_address=? LIMIT 1");
    }

    $set = yield $statement->execute([$email]);
    $row = yield $set->fetchObject();

    return $row;
}

function getUserByUsername(string $username)
{
    static $statement;

    if (isset($statement) === false) {
        $statement = yield connectionPool()->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    }

    $set = yield $statement->execute([$username]);
    $row = yield $set->fetchObject();

    return $row;
}

function getUserByName(string $name)
{
    static $statement;

    if (isset($statement) === false) {
        $statement = yield connectionPool()->prepare("SELECT * FROM users WHERE name=? LIMIT 1");
    }

    $set = yield $statement->execute([$name]);
    $row = yield $set->fetchObject();

    return $row;
}