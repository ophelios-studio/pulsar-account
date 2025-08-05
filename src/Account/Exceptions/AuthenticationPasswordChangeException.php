<?php namespace Pulsar\Account\Exceptions;

class AuthenticationPasswordChangeException extends AuthenticationException
{
    public function __construct(string $message)
    {
        parent::__construct("An error happened while attempting to update the user password [$message].", 70006);
        $this->setUserMessage($message);
    }
}
