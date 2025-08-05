<?php namespace Pulsar\Account\Exceptions;

use Exception;

abstract class AuthenticationException extends Exception
{
    /**
     * Message destined for the end user and not the developers.
     *
     * @var string
     */
    private string $userMessage;

    public function __construct(string $message, string $userMessage = "")
    {
        parent::__construct('AUTHENTICATION: ' . $message);
        $this->userMessage = $userMessage;
//        Logger::system("logs.exception.pulsar")
//            ->addArgument("message", $this->getMessage())
//            ->addArgument("file", $this->getFile())
//            ->addArgument("line", $this->getLine())
//            ->severity(Logger::ERROR)
//            ->log();
    }

    public function setUserMessage(string $message): void
    {
        $this->userMessage = $message;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}
