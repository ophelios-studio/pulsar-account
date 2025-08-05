<?php namespace Pulsar\Account\Exceptions;

use Exception;

class PwnedToleranceException extends Exception
{
    public function __construct()
    {
        parent::__construct("Pwned tolerance configuration property must be int (number of leaks tolerated before refusing a password, default to 1).");
    }
}
