<?php namespace Pulsar\Account;

use Pulsar\Account\Configurations\PwnedConfiguration;
use Zephyrus\Core\Configuration;

class Utilities
{
    public static function getPwnedConfiguration(): PwnedConfiguration
    {
        return new PwnedConfiguration(Configuration::read('security')['pwned']
            ?? PwnedConfiguration::DEFAULT_CONFIGURATIONS);
    }
}
