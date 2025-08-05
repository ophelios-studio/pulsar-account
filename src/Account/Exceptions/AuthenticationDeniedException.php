<?php namespace Pulsar\Account\Exceptions;

class AuthenticationDeniedException extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct("The authentication credentials are invalid.",
            localize("accounts.errors.denied"));
    }
}
