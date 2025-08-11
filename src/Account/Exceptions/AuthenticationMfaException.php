<?php namespace Pulsar\Account\Exceptions;

use Zephyrus\Core\Session;
use Zephyrus\Security\Cryptography;

class AuthenticationMfaException extends AuthenticationException
{
    protected string $state;
    protected string $username;
    protected string $type;
    protected bool $remember;

    public function __construct(string $username, string $type, bool $remember)
    {
        parent::__construct("Your account is protected by multi-factor authentication.");
        $this->state = Cryptography::randomString(16);
        $this->username = $username;
        $this->type = $type;
        $this->remember = $remember;
        Session::set('mfa_state', $this->state);
        Session::set('mfa_username', $username);
        Session::set('mfa_type', $type);
        Session::set('mfa_remember', $remember); // To allow auto-connect pass the MFA checks
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRemember(): bool
    {
        return $this->remember;
    }
}
