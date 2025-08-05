<?php namespace Pulsar\Account\Exceptions;

use Zephyrus\Utilities\Formatter;

class AuthenticationBruteForceException extends AuthenticationException
{
    private string $bannedIpAddress;
    private string $username;
    private int $unbannedTime;
    private int $maxFailedAttempts;

    public function __construct(string $username, string $bannedIpAddress, int $unbannedTime, int $maxFailedAttempts)
    {
        $this->username = $username;
        $this->bannedIpAddress = $bannedIpAddress;
        $this->unbannedTime = $unbannedTime;
        $this->maxFailedAttempts = $maxFailedAttempts;
        $remainingTime = Formatter::duration($unbannedTime - time());
        parent::__construct("Wrong password attempts limit ($maxFailedAttempts) reached for username $username. Your ip address $bannedIpAddress will be locked for $remainingTime.");
        $this->setUserMessage(localize("accounts.errors.lockdown", $maxFailedAttempts, $username, $bannedIpAddress, $remainingTime));
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getBannedIpAddress(): string
    {
        return $this->bannedIpAddress;
    }

    public function getUnbannedTime(): int
    {
        return $this->unbannedTime;
    }

    public function getMaxFailedAttempts(): int
    {
        return $this->maxFailedAttempts;
    }
}
