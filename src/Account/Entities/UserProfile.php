<?php namespace Pulsar\Account\Entities;

use Zephyrus\Core\Entity\Entity;

class UserProfile extends Entity
{
    public int $id;
    public string $firstname;
    public string $lastname;
    public string $fullname;
    public string $initials;
    public string $username;
    public string $email;
    public ?string $avatar;
    public bool $online;
    public string $created_at;
}
