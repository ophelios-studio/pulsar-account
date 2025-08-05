<?php namespace Pulsar\Account\Brokers;

use Pulsar\Account\Entities\User;
use stdClass;
use Zephyrus\Core\Application;
use Zephyrus\Core\Configuration;
use Zephyrus\Database\DatabaseBroker;

class UserSettingBroker extends DatabaseBroker
{
    private Application $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
        parent::__construct($application->getDatabase());
    }

    public function insert(int $userId, stdClass $new): void
    {
        $sql = "INSERT INTO pulsar.user_setting(id, preferred_locale, last_release_seen) 
                VALUES(?, ?, ?)";
        $this->query($sql, [
            $userId,
            $new->preferred_locale ?? Configuration::getLocale()->getLocale(),
            $this->application->getVersion()
        ]);
    }

    public function update(User $old, stdClass $new): void
    {
        $sql = "UPDATE pulsar.user_setting 
                   SET preferred_locale = ? 
                 WHERE id = ?";
        $this->query($sql, [
            $new->preferred_locale ?? Configuration::getLocale()->getLocale(),
            $old->id
        ]);
    }

    public function updateLastRelease(int $userId): void
    {
        $this->query("UPDATE pulsar.user_setting SET last_release_seen = ? WHERE id = ?", [
            $this->application->getVersion(),
            $userId
        ]);
    }

    public function skipTwoFactorWarning(int $userId): void
    {
        $this->query("UPDATE pulsar.user_setting SET skip_mfa_warning = TRUE WHERE id = ?", [$userId]);
    }
}
