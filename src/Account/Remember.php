<?php namespace Pulsar\Account;

use Pulsar\Account\Exceptions\RecognizerException;
use stdClass;
use Zephyrus\Core\Application;
use Zephyrus\Network\Cookie;
use Zephyrus\Security\Cryptography;

class Remember
{
    public const string REMEMBER_COOKIE_NAME = "remember";
    public const string SEQUENCER_COOKIE_NAME = "sequencer";

    private string $identifier;
    private string $validator;
    private string $sequence;
    private string $ipAddress;
    private string $userAgent;

    public static function generate(): self
    {
        $instance = new self();
        $instance->identifier = base64_encode(Cryptography::randomBytes(12)); // produces 16 bytes, to store in database
        $instance->validator = base64_encode(Cryptography::randomBytes(24)); // produces 32 bytes
        $instance->sequence = base64_encode(Cryptography::randomBytes(60)); // 80 bytes, regenerated each login ...
        return $instance;
    }

    /**
     * @throws RecognizerException
     */
    public static function recognize(): ?self
    {
        if (!isset($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
            return null;
        }
        $token = $_COOKIE[self::REMEMBER_COOKIE_NAME];

        if (!isset($_COOKIE[self::SEQUENCER_COOKIE_NAME])) {
            throw new RecognizerException(RecognizerException::ERR_SEQUENCER);
        }
        $sequencer = $_COOKIE[self::SEQUENCER_COOKIE_NAME];

        if (strlen($token) != 48) {
            throw new RecognizerException(RecognizerException::ERR_INVALID_FORMAT);
        }
        if (strlen($sequencer) != 80) {
            throw new RecognizerException(RecognizerException::ERR_INVALID_FORMAT);
        }

        $instance = new self();
        $instance->identifier = (substr($token, 0, 16));
        $instance->validator = (substr($token, 16, 32));
        $instance->sequence = $sequencer;
        return $instance;
    }

    public static function destroy(): void
    {
        new Cookie(self::REMEMBER_COOKIE_NAME)->destroy();
        new Cookie(self::SEQUENCER_COOKIE_NAME)->destroy();
    }

    public function regenerateSequence(): void
    {
        $this->sequence = base64_encode(Cryptography::randomBytes(60));
    }

    public function sendCookies(int $duration = Cookie::DURATION_MONTH): void
    {
        $this->sendRememberCookie($duration);
        $this->sendSequenceCookie($duration);
    }

    /**
     * @param stdClass $token
     * @throws RecognizerException
     */
    public function validateAuthenticationToken(stdClass $token): void
    {
        if (is_null($token)) {
            throw new RecognizerException(RecognizerException::ERR_INVALID_ENTITY);
        }

        $neededProperties = ['validation', 'iteration', 'user_agent'];
        foreach ($neededProperties as $property) {
            if (!isset($token->$property)) {
                throw new RecognizerException(RecognizerException::ERR_INVALID_ENTITY_FORMAT);
            }
        }

        if ($token->iteration != $this->sequence) {
            throw new RecognizerException(RecognizerException::ERR_SEQUENCER_MISMATCH);
        }

        if ($token->user_agent->raw != $this->userAgent) {
            throw new RecognizerException(RecognizerException::ERR_USER_AGENT_MISMATCH);
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getValidator(): string
    {
        return $this->validator;
    }

    public function getSequence(): string
    {
        return $this->sequence;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    private function __construct()
    {
        $request = Application::getInstance()->getRequest();
        $this->ipAddress = $request->getClientIp();
        $this->userAgent = $request->getUserAgent();
    }

    public function sendRememberCookie(int $duration = Cookie::DURATION_DAY * 30): void
    {
        $cookie = new Cookie(self::REMEMBER_COOKIE_NAME, $this->identifier . $this->validator);
        $cookie->setLifetime($duration);
        $cookie->send();
    }

    public function sendSequenceCookie(int $duration = Cookie::DURATION_DAY * 30): void
    {
        $cookie = new Cookie(self::SEQUENCER_COOKIE_NAME, $this->sequence);
        $cookie->setLifetime($duration);
        $cookie->send();
    }
}
