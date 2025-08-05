<?php namespace Pulsar\Account\Exceptions;

class AuthenticationNotConfirmedException extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct("The account is not confirmed yet.",
            localize("accounts.errors.not_activated"));
    }
}
