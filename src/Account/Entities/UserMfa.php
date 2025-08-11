<?php namespace Pulsar\Account\Entities;

use Zephyrus\Core\Entity\Entity;

class UserMfa extends Entity
{
    public int $id;
    public string $type;
    public ?string $secret;
    public bool $is_primary;
    public string $created_at;
}
