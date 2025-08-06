<?php namespace Pulsar\Account\Services;

use Pulsar\Account\Brokers\RememberTokenBroker;
use Pulsar\Account\Entities\User;
use Pulsar\Account\Remember;
use stdClass;
use Zephyrus\Network\Cookie;

class RememberTokenService
{
    public static function read(int $userId, int $tokenId): ?stdClass
    {
        return new RememberTokenBroker($userId)->findById($tokenId);
    }

    public static function readByIdentifier(int $userId, string $identifier): ?stdClass
    {
        return new RememberTokenBroker($userId)->findByTokenIdentifier($identifier);
    }

    public static function remember(User $user): void
    {
        $remember = Remember::generate();
        new RememberTokenBroker($user->id)->remember((object) [
            'identifier' => $remember->getIdentifier(),
            'validator' => $remember->getValidator(),
            'sequence' => $remember->getSequence(),
            'user_agent' => $remember->getUserAgent(),
            'ip_address' => $remember->getIpAddress(),
            'expire' => date(FORMAT_DATE_TIME, time() + Cookie::DURATION_DAY * 30),
            'user_id' => $user->id
        ]);
        $remember->sendCookies();
    }

    public static function updateSequence(int $userId, int $tokenId, string $sequence): void
    {
        new RememberTokenBroker($userId)->updateSequence($tokenId, $sequence);
    }

    public static function remove(int $userId, int $tokenId): void
    {
        new RememberTokenBroker($userId)->delete($tokenId);
        setcookie('remember', '', time() - 3600, '/');
    }
}
