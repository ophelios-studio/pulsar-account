<?php namespace Pulsar\Account\Entities;

use Zephyrus\Core\Entity\Entity;

class UserAuthentication extends Entity
{
    public string $username;
    public ?string $password_hash; // Can be null is using oauth
    public bool $password_compromised;
    public bool $password_reset;
    public ?string $activation; // NULL when the user is confirmed
    public string $validator;
    public ?string $grace_secret;
    public bool $locked;
    public ?string $last_connection; // Can be null is never connected
    public bool $superuser;
    public string $login_provider;
    public ?string $login_provider_user_id;
    public ?string $login_provider_access_token;

    /**
     * @var UserMfa[]
     */
    public ?array $mfa_methods;

    public function hasMfa(): bool
    {
        return !empty($this->mfa_methods);
    }
}
