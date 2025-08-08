<?php namespace Pulsar\Account\Exceptions;

use Zephyrus\Core\Session;
use Zephyrus\Security\Cryptography;

class AuthenticationPasswordCompromisedException extends AuthenticationException
{
    private string $state;
    private string $username;

    public function __construct(string $username)
    {
        parent::__construct("Your password has been compromised and you need to change it before you can login.");
        $this->state = Cryptography::randomString(16);
        $this->username = $username;
        Session::set('breached_password_state', $this->state);
        Session::set('breached_password_username', $username);
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
