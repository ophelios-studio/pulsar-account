<?php namespace Pulsar\Account\Entities;

class User extends UserProfile
{
    public UserAuthentication $authentication;
    public ?UserSetting $setting;

    public function isActivated(): bool
    {
        return is_null($this->authentication->activation);
    }

    public function isLocked(): bool
    {
        return $this->authentication->locked;
    }
}
