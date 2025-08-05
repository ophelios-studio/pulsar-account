<?php namespace Pulsar\Account\Entities;

use Zephyrus\Core\Entity\Entity;

class UserSetting extends Entity
{
    public bool $skip_mfa_warning;
    public string $preferred_locale;
    public string $last_release_seen;
}
