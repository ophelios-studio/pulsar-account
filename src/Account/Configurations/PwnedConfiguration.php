<?php namespace Pulsar\Account\Configurations;

use Pulsar\Account\Exceptions\PwnedToleranceException;

class PwnedConfiguration
{
    public const array DEFAULT_CONFIGURATIONS = [
        'enabled' => true, // Activate the pwned api check
        'tolerance' => 1 // Number of leaks tolerated before refusing the password
    ];

    private array $configurations;
    private bool $enabled;
    private int $tolerance;

    /**
     * @throws PwnedToleranceException
     */
    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializeEnabled();
        $this->initializeTolerance();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getTolerance(): int
    {
        return $this->tolerance;
    }

    private function initializeConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    private function initializeEnabled(): void
    {
        $this->enabled = (bool) ((isset($this->configurations['enabled']))
            ? $this->configurations['enabled']
            : self::DEFAULT_CONFIGURATIONS['enabled']);
    }

    /**
     * @throws PwnedToleranceException
     */
    private function initializeTolerance(): void
    {
        $tolerance = $this->configurations['tolerance'] ?? self::DEFAULT_CONFIGURATIONS['tolerance'];
        if (!is_numeric($tolerance)) {
            throw new PwnedToleranceException();
        }
        $this->tolerance = $tolerance;
    }
}
