<?php namespace Pulsar\Account\Brokers;

use Pulsar\Account\Entities\User;
use Zephyrus\Database\DatabaseBroker;

class UserMfaBroker extends DatabaseBroker
{
    private User $user;

    public function __construct(User $user)
    {
        parent::__construct();
        $this->user = $user;
    }

    public function insert(string $type, ?string $secret = null): int
    {
        $primary = is_null($this->user->authentication->primary_mfa);
        $sql = "INSERT INTO pulsar.user_mfa(type, secret, user_id, is_primary) 
                VALUES(?, ?, ?, ?) RETURNING id";
        return $this->query($sql, [
            $type,
            $secret,
            $this->user->id,
            $primary
        ])->id;
    }

    public function exists(int $identifier): bool
    {
        $sql = "SELECT COUNT(*) as n FROM pulsar.user_mfa WHERE user_id = ? AND id = ?";
        return $this->selectSingle($sql, [$this->user->id, $identifier])->n > 0;
    }

    public function delete(string $type): void
    {
        $mfa = $this->user->authentication->getMfa($type);
        if ($mfa?->is_primary) {
            foreach ($this->user->authentication->mfa_methods as $method) {
                if ($method->type != $type) {
                    $this->setPrimary($method->id);
                    break;
                }
            }
        }
        $sql = "DELETE FROM pulsar.user_mfa WHERE user_id = ? AND type = ?";
        $this->query($sql, [
            $this->user->id,
            $type
        ]);
    }

    public function setPrimary(int $newMfaId): void
    {
        $sql = "UPDATE pulsar.user_mfa SET is_primary = FALSE WHERE user_id = ?";
        $this->query($sql, [
            $this->user->id
        ]);

        $sql = "UPDATE pulsar.user_mfa SET is_primary = TRUE WHERE user_id = ? AND id = ?";
        $this->query($sql, [$this->user->id, $newMfaId]);
    }
}
