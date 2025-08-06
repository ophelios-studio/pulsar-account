<?php namespace Pulsar\Account\Exceptions;

class RecognizerException extends \Exception
{
    const ERR_INVALID_ENTITY_FORMAT = 900;
    const ERR_INVALID_FORMAT = 901;
    const ERR_INVALID_ENTITY = 902;
    const ERR_SEQUENCER = 903;
    const ERR_INVALID_VALUE = 904;
    const ERR_SEQUENCER_MISMATCH = 905;
    const ERR_USER_AGENT_MISMATCH = 906;

    public function __construct($code)
    {
        parent::__construct($this->codeToMessage($code), $code);
    }

    private function codeToMessage($code)
    {
        switch ($code) {
            case self::ERR_INVALID_FORMAT:
                $message = "Provided authentication cookie has not the proper format";
                break;
            case self::ERR_INVALID_ENTITY_FORMAT:
                $message = "Missing property in token object. Should include ['validation', 'iteration', 'user_agent']";
                break;
            case self::ERR_INVALID_ENTITY:
                $message = "Invalid token identifier (no match found)";
                break;
            case self::ERR_SEQUENCER:
                $message = "Missing sequencer token (possible theft of remember cookie)";
                break;
            case self::ERR_INVALID_VALUE:
                $message = "Provided authentication token value does not match";
                break;
            case self::ERR_SEQUENCER_MISMATCH:
                $message = "Sequencer mismatch! Cookies may have been stolen.";
                break;
            case self::ERR_USER_AGENT_MISMATCH:
                $message = "UserAgent mismatch! Cookies may have been stolen.";
                break;
            default:
                $message = "Unknown token error";
                break;
        }
        return $message;
    }
}
