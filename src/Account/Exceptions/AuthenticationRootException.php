<?php namespace Pulsar\Account\Exceptions;

class AuthenticationRootException extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct("The root system user account cannot be used for standard login.");
        $this->setUserMessage(localize("accounts.errors.root_user"));
    }
}
