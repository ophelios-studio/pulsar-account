<?php namespace Pulsar\Account\Brokers;

use stdClass;
use Zephyrus\Database\DatabaseBroker;
use Zephyrus\Security\Cryptography;

class RememberTokenBroker extends DatabaseBroker
{
    private int $userId;

    public function __construct(int $userId)
    {
        parent::__construct();
        $this->userId = $userId;
    }

    public function findById(int $authenticationTokenId): ?stdClass
    {
        return $this->selectSingle("SELECT * FROM pulsar.view_user_remember_token WHERE user_id = ? AND id = ?", [$this->userId, $authenticationTokenId]);
    }

    public function findByTokenIdentifier(string $identifier): ?stdClass
    {
        return $this->selectSingle("SELECT * FROM pulsar.view_user_remember_token WHERE user_id = ? AND identifier = ?", [$this->userId, $identifier]);
    }

    public function remember(stdClass $remember): void
    {
        $sql = "DELETE FROM pulsar.user_remember_token WHERE user_id = ? and user_agent->>'raw' = ?";
        $this->query($sql, [$remember->user_id, $remember->user_agent->raw]);

        $sql = "INSERT INTO pulsar.user_remember_token(identifier, validation, iteration, user_agent, ip_address, user_id, expire) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->query($sql, [
            $remember->identifier, // Equivalent of "username"
            Cryptography::hashPassword($remember->validator), // Equivalent of "password"
            $remember->sequence, // Update on each login for security measure
            json_encode($remember->user_agent),
            $remember->ip_address,
            $this->userId,
            $remember->expire
        ]);
    }

    public function updateSequence(int $authenticationTokenId, string $sequence): void
    {
        $sql = 'UPDATE pulsar.user_remember_token SET iteration = ? WHERE id = ?';
        $this->query($sql, [$sequence, $authenticationTokenId]);
    }

    public function delete(int $authenticationTokenId): int
    {
        $sql = "DELETE FROM pulsar.user_remember_token WHERE id = ? AND user_id = ?";
        $this->query($sql, [$authenticationTokenId, $this->userId]);
        return $this->getLastAffectedCount();
    }
}

