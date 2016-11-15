<?php

namespace Zephyr\Model\User;

function fetchUserFromExtSource(array $data, string $how, string $source)
{
    $sourceNamespace = ucwords(strtolower($source));
    $namespace = "Zephyr\Api\${sourceNamespace}";
    $method = null;
    $param = null;
    $userInformation = [];

    if ($how === 'username') {
        $method = "$namespace\getUserByUsername";
        $param = $data[$how];
    } else if ($how === 'name') {
        $method = "$namespace\getUserByName";
        $param = $data[$how];
    } else if ($how === 'email_address') {
        $method = "$namespace\getUserByEmail";
        $param = $data[$how];
    } else if ($how === 'accounts') {
        $method = "$namespace\getUserById";
        foreach($data['accounts'] as $account) {
            if (isset($account['account_identifier']) === false) {
                continue;
            }

            if ($account['account_type'] === $source) {
                $param = $account['account_identifier'];
                break;
            }
        }
    }

    if (function_exists($method)) {
        $result = yield $method($param);

        if ($result == false) {
            throw new \Exception("Could not resolve user information. Type: $how");
        }

        // Assume we're only dealing with Gitlab at the moment.
        $userInformation['username'] = $result['username'];
        $userInformation['email_address'] = $result['email'];
        $userInformation['name'] = $result['name'];
    }

    return $userInformation;    
}

function createUserPartial(array $data, string $extSource)
{
    $hasUsername = isset($data['username']);
    $hasName = isset($data['name']);
    $hasEmail = isset($data['email_address']);
    $hasAccounts = isset($data['accounts']);

    // Rquire username, email and name.
    $needsInformation = $hasUsername === false || $hasEmail === false || $hasName === false;

    if ($needsInformation) {
        // Determine how we're going to ask for the missing information.
        $how = null;

        if ($hasUsername) {
            $how = "username";
        } else if ($hasName) {
            $how = "name";
        } else if ($hasEmail) {
            $how = "email_address";
        } else if ($hasAccounts) {
            $how = "accounts";
        }

        if ($how) {
            $userInformation = yield fetchUserFromExtSource($data, $how, $extSource);

            if ($userInformation == false) {
                throw new \Exception("Could not create user from partial information.");
            }

            $data = array_merge($data, $userInformation);

            yield createUser($data);
        }
    }

    return $data;
}

function createUser(array $data)
{
    static $statement;
    $result = false;

    if (isset($data['email_address']) === false) {
        throw new \Exception("Email address must be provided when creating a new user.");
    } else if (isset($data['username']) === false) {
        throw new \Exception("Username must be provided when creating a new user.");
    } else if (isset($data['name']) === false) {
        throw new \Exception("Name must be provided when creating a new user.");
    } else {

        if (isset($statement) === false) {
            $statement = yield Zephyr\Helpers\connectionPool()->prepare("INSERT INTO users (email_address, username, name) VALUES (:email_address, :username, :name)");
        }

        $statement->execute($data);

        if (isset($data['accounts']) === true) {
            $promises = [];
            foreach($data['accounts'] as $account) {
                $account['user_email_address'] = $data['email_address'];
                $promises[] = Zephyr\Model\Account\createUserAccount($account);
            }

            yield Amp\all($promises);
        }

        $result = $data;
    }

    return $result;
}

function getUserByEmail(string $email)
{
    static $statement;

    if (isset($statement) === false) {
        $statement = yield Zephyr\Helpers\connectionPool()->prepare("SELECT * FROM users WHERE email_address=? LIMIT 1");
    }

    $set = yield $statement->execute([$email]);
    $row = yield $set->fetch();

    return $row;
}

function getUserByUsername(string $username)
{
    static $statement;

    if (isset($statement) === false) {
        $statement = yield Zephyr\Helpers\connectionPool()->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    }

    $set = yield $statement->execute([$username]);
    $row = yield $set->fetch();

    return $row;
}

function getUserByName(string $name)
{
    static $statement;

    if (isset($statement) === false) {
        $statement = yield Zephyr\Helpers\connectionPool()->prepare("SELECT * FROM users WHERE name=? LIMIT 1");
    }

    $set = yield $statement->execute([$name]);
    $row = yield $set->fetch();

    return $row;
}

function getUserByAccountId(int $id, string $type)
{
    static $statement;

    if (isset($statement) === false) {
        $statement = yield Zephyr\Helpers\connectionPool()->prepare("SELECT users.* FROM account_lookup INNER JOIN users ON (users.email_address=account_lookup.user_email_address) WHERE account_lookup.account_identifier=:id AND account_lookup.account_type=:type");
    }

    $set = yield $statement->execute(compact('id', 'type'));
    $row = yield $set->fetch();

    return $row;
}