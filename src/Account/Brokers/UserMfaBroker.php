<?php namespace Pulsar\Account\Brokers;

use Pulsar\Account\Entities\User;
use Zephyrus\Database\DatabaseBroker;

class UserMfaBroker extends DatabaseBroker
{
    public function insert(User $user, string $type, ?string $secret = null): void
    {
        $sql = "INSERT INTO pulsar.user_mfa(type, secret, user_id) 
                VALUES(?, ?, ?)";
        $this->query($sql, [
            $type,
            $secret,
            $user->id
        ]);
    }

    public function delete(User $old, string $type): void
    {
        $sql = "DELETE FROM pulsar.user_mfa WHERE user_id = ? AND type = ?";
        $this->query($sql, [
            $old->id,
            $type
        ]);
    }
}
