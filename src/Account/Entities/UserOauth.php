<?php namespace Pulsar\Account\Entities;

use Zephyrus\Core\Entity\Entity;

class UserOauth extends Entity
{
    public string $provider;
    public string $provider_user_id;
    public string $access_token;
    public string $connected_at;
}
