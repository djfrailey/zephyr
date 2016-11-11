<?php

namespace Zephyr\Model\Account;

use function Zephyr\Helpers\connectionPool;
use Amp\{Deferred, Promise, Failure, Success};
use \stdClass;
use \Exception;

function createUserAccount(array $data)
{
    static $statement;

    if (empty($data['user_email_address'])) {
        throw new Exception("User email address is required to create a new account record.");
    }

    if (empty($data['account_identifier'])) {
        throw new Exception("An account identifer is required to create a new account record.");
    }

    if (empty($data["account_type"])) {
        throw new Exception("An account type is required to create a new account record.");
    }

    if (isset($statement) === false) {
        $statement = yield connectionPool()->prepare("INSERT INTO account_lookup (user_email_address, account_identifier, account_type) VALUES (:user_email_address, :account_identifier, :account_type)");
    }

    yield $statement->execute($data);

    return true;
}