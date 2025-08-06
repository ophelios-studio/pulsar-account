<?php namespace Pulsar\Account\Entities;

use stdClass;

class User extends UserProfile
{
    public UserAuthentication $authentication;
    public ?UserSetting $setting;
    public ?stdClass $matching_token = null; // For remember me

    public function isActivated(): bool
    {
        return is_null($this->authentication->activation);
    }

    public function isLocked(): bool
    {
        return $this->authentication->locked;
    }
}
