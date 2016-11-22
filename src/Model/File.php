<?php

declare (strict_types=1);

namespace Zephyr\Model\File;

use Amp\File\Handle;
use \Exception;
use function Amp\File\{ 
    exists,
    touch,
    open
};
use Amp\{
    Deferred,
    Promise,
    Success,
    function wait
};

const DATADIR = './src/Data/';

/**
 * Returns the data stored inside of a user data file.
 * @param  string $user
 * @return Promise
 */
function getUserData(string $user)
{

    $promisor = new Deferred();

    $onOpen = function($error, $handle) use ($user, $promisor) {

        if ($error) {
            $promisor->fail($error);
        } else {

            $handle->read(10 * 1024)->when(function($error, $data) use ($promisor, $handle) {
                if ($error) {
                    $promisor->fail($error);
                } else {

                    if ($data) {
                        $data = json_decode($data, true);
                    }

                    if (empty($data)) {
                        $data = [];
                    }
                    
                    $handle->close();

                    $promisor->succeed($data);

                }
            });
        }
    };

    $open = openUserFile($user, 'r')->when($onOpen);

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
            $promisor->fail($error);
        } else {
            $data = json_encode($data);
            
            $handle->write($data)->when(function($error, $result) use ($promisor, $handle) {

                if ($error) {
                    $promisor->fail($error);
                } else {
                    $handle->close();
                    $promisor->succeed($result);
                }


            });
        }
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
    $fullpath = DATADIR . $filename;

    $onTouched = function($error,$handle) use($promisor, $fullpath) {
        
        if ($error) {
            $promisor->fail($error);
        } else {

            $handle->close();
            $promisor->succeed($fullpath);

        }
    };
    
    open($fullpath, 'w')->when($onTouched);

    return $promisor->promise();
}

/**
 * Opens a user file if it exists, or creates it, then returns a file handle to be used.
 * @param  string $user
 * @param  string $mode
 * @return Promise 
 */
function openUserFile(string $user, string $mode)
{
    $userFile = DATADIR . $user;

    $promisor = new Deferred;

    $onOpen = function($error, $result) use ($promisor) {
        if ($error) {
            $promisor->fail($error);
        } else {
            $promisor->succeed($result);
        }
    };

    $onCreated = function($error, $result) use ($onOpen, $userFile, $mode, $promisor) {
        if ($error) {
            $promisor->fail($error);
        } else {
            open($userFile, $mode)->when($onOpen);
        }
    };

    $onExists = function($error, $result) use ($onCreated, $onOpen, $user, $userFile, $mode, $promisor) {
        if ($error) {
            $promisor->fail($error);
        } else {
            
            if ($result === false) {
                createDataFile($user)->when($onCreated);
            } else {
                open($userFile, $mode)->when($onOpen);
            }
        
        }
    };

    exists($userFile)->when($onExists);

    return $promisor->promise();
}

function getUserfilenameById(int $id) : String
{
    return "user-${id}.json";
}