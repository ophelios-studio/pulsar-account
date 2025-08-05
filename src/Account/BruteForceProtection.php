<?php namespace Pulsar\Account;

use Pulsar\Account\Exceptions\AuthenticationBruteForceException;
use Zephyrus\Core\Cache\ApcuCache;

class BruteForceProtection
{
    private ApcuCache $cache;
    private int $allowedTries;
    private int $delay = 2;
    private int $exponentialPunishDelay = 60;

    public function __construct(int $allowedTries = 3)
    {
        $this->allowedTries = $allowedTries;
        $this->cache = new ApcuCache();
    }

    /**
     * Verify if the username is banned. Throws an exception if the user is considered locked.
     *
     * @param string $username
     * @throws AuthenticationBruteForceException
     */
    public function verify(string $username): void
    {
        $keys = $this->getCacheKeys($username);
        $tries = $this->cache->get($keys['attempts']) ?? 0;
        $unbannedTime = $this->cache->get($keys['unbanned_time']) ?? 0;
        if ($tries + 1 >= $this->allowedTries) {
            throw new AuthenticationBruteForceException($username, $_SERVER['REMOTE_ADDR'], $unbannedTime, $this->allowedTries);
        }
    }

    /**
     * Increment failed attempt count for a specific username.
     *
     * @param string $username
     */
    public function mitigate(string $username): void
    {
        $keys = $this->getCacheKeys($username);
        $attempts = $this->cache->get($keys['attempts']) ?? 0;
        $totalAttempts = $this->cache->get($keys['total_attempts']) ?? 0;

        $timeToLive = pow(2, $totalAttempts + 1) * $this->exponentialPunishDelay;
        $this->cache->set($keys['attempts'], $attempts + 1, $timeToLive);
        $this->cache->set($keys['total_attempts'], $totalAttempts + 1, 86400);
        $this->cache->set($keys['unbanned_time'], (int) (time() + $timeToLive), 86400);

        sleep($this->delay);
    }

    /**
     * Clear brute force data for a specific username.
     *
     * @param string $username
     */
    public function clear(string $username): void
    {
        $keys = $this->getCacheKeys($username);
        $this->cache->delete($keys['attempts']);
        $this->cache->delete($keys['total_attempts']);
        $this->cache->delete($keys['unbanned_time']);
    }

    public function getAllowedTries(): int
    {
        return $this->allowedTries;
    }

    public function setAllowedTries(int $allowedTries): void
    {
        $this->allowedTries = $allowedTries;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $seconds): void
    {
        $this->delay = $seconds;
    }

    public function getExponentialPunishDelay(): int
    {
        return $this->exponentialPunishDelay;
    }

    public function setExponentialPunishDelay(int $seconds): void
    {
        $this->exponentialPunishDelay = $seconds;
    }

    /**
     * Get remaining allowed attempts for the given username.
     *
     * @param string $username
     * @return int
     */
    public function getRemainingAttemptsBeforeBan(string $username): int
    {
        $keys = $this->getCacheKeys($username);
        $remaining = $this->allowedTries - $this->cache->get($keys['attempts'], 0);
        return max($remaining, 0);
    }

    /**
     * Generate cache keys based on username and prevent conflicts.
     *
     * @param string $username
     * @return array
     */
    private function getCacheKeys(string $username): array
    {
        $username = base64_encode($username);
        $baseKey = "{$_SERVER['SERVER_NAME']}|login-attempt|$username";
        return [
            'attempts' => "$baseKey|attempts",
            'total_attempts' => "$baseKey|total",
            'unbanned_time' => "$baseKey|unbanned",
        ];
    }
}