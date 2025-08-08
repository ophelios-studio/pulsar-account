<?php namespace Pulsar\Account\Exceptions;

use Zephyrus\Core\Session;
use Zephyrus\Security\Cryptography;

class AuthenticationPasswordResetException extends AuthenticationException
{
    protected string $state;
    protected string $username;

    public function __construct(string $username)
    {
        parent::__construct("You need to change your password before you can login.");
        $this->state = Cryptography::randomString(16);
        $this->username = $username;
        Session::set('reset_password_state', $this->state);
        Session::set('reset_password_username', $username);
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
