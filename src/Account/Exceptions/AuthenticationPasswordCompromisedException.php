<?php namespace Pulsar\Account\Exceptions;

class AuthenticationPasswordCompromisedException extends AuthenticationPasswordResetException
{
    public function __construct(string $username)
    {
        parent::__construct($username,
            "Your password has been compromised and you need to change it before you can login.");
    }

}
