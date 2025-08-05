<?php namespace Pulsar\Account\Exceptions;

class AuthenticationLockedException extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct("The authenticated user account has been locked.");
        $this->setUserMessage(localize("accounts.errors.locked"));
    }
}
