<?php

declare (strict_types=1);

namespace Zephyr\Model\File;

use \Exception;
use function Amp\File\{ 
    exists,
    touch,
    open
};
use Amp\{
    Deferred,
    Promise,
    Success
};

const DATADIR = './src/Data/';

/**
 * Returns the data stored inside of a user data file.
 * @param  string $user
 * @return Promise
 */
function getUserData(string $user) : Promise
{

    $promisor = new Deferred();

    $onOpen = function($error, $handle) use ($user, $promisor) {

        if ($error) {
            $promisor->failed($error);
        }

        $data = yield $handle->read(10 * 1024);
        yield $handle->close();

        if ($data) {
            $data = json_decode($data, true);
        }

        if (empty($data)) {
            $data = [];
        }

        $promisor->succeed($data);
    };

    openUserFile($user, 'r')->when($onOpen);

    return $promisor->promise();
}

/**
 * Overwrites the data stored in a user data file.
 * @param  string $user 
 * @param  mixed $data 
 * @return Promise
 */
function writeUserData(string $user, $data) : Promise
{

    $promisor = new Deferred();
    $onOpen = function($error, $handle) use ($user, $data, $promisor) {

        if ($error) {
            $promisor->failed($error);
        }

        $data = json_encode($data);
        
        $result = $handle->write($data);
        
        yield $handle->close();

        $promisor->succeed($result);
    };

    openUserFile($user, 'w')->when($onOpen);

    return $promisor->promise();;
}

/**
 * Creates a user data file.
 * @param  string $filename
 * @return Promise
 */
function createDataFile(string $filename) : Promise
{
    $promisor = new Deferred;
    $filename = rtrim($filename, '.json');
    $filename .= '.json';
    $fullpath = DATADIR . $filename;

    $onTouched = function($error, $result) use($promisor, $fullpath) {
        
        if ($error) {
            $promisor->fail($error);
        }

        $promisor->succeed($fullpath);
    };
    
    $promise = touch($fullpath);
    $promise->when($onTouched);

    return $promisor->promise();
}

/**
 * Opens a user file if it exists, or creates it, then returns a file handle to be used.
 * @param  string $user
 * @param  string $mode
 * @return Promise 
 */
function openUserFile(string $user, string $mode) : Promise
{
    $user = rtrim($user, '.json');
    $userFile = DATADIR . $user . '.json';

    $promisor = new Deferred;

    $onOpen = function($error, $result) use ($promisor) {
        if ($error) {
            $promisor->failed($openError);
        }

        $promisor->succeed($result);
    };

    $onCreated = function($error, $result) use ($onOpen, $userFile, $mode) {
        if ($error) {
            throw new Exception($error);
        }

        open($userFile, $mode)->when($onOpen);
    };

    $onExists = function($error, $result) use ($onCreated, $onOpen, $user, $userFile, $mode) {
        if ($error) {
            throw new Exception($error);
        }

        if ($result === false) {
            createDataFile($user)->when($onCreated);
        } else {
            open($userFile, $mode)->when($onOpen);
        }
    };

    exists($userFile)->when($onExists);

    return $promisor->promise();
}