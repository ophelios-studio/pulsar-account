<?php namespace Pulsar\Account;

use Pulsar\Account\Api\PwnedApi;
use Pulsar\Account\Brokers\UserBroker;
use Zephyrus\Application\Rule;
use Zephyrus\Core\Application;
use Zephyrus\Security\Cryptography;

class AccountRules
{
    public static function username(): Rule
    {
        return new Rule(function ($username) {
            return preg_match("/^[a-zA-Z0-9_\-.]+$/", $username);
        }, localize("accounts.errors.username_invalid"));
    }

    public static function locale(?string $errorMessage = null): Rule
    {
        return new Rule(function ($value) {
            $languages = Application::getInstance()->getSupportedLanguages();
            $locales = [];
            foreach ($languages as $language) {
                $locales[] = $language->locale;
            }
            return in_array($value, $locales);
        }, $errorMessage ?? localize("accounts.errors.locale_invalid"));
    }

    public static function passwordBreach(): Rule
    {
        $rule = new Rule();
        $rule->setValidationCallback(function ($password) use ($rule) {
            $config = Utilities::getPwnedConfiguration();
            if (!$config->isEnabled()) {
                return true;
            }
            $count = PwnedApi::findBreachCount($password);
            $rule->setErrorMessage(localize("accounts.errors.password_breached", $count));
            return $count < $config->getTolerance();
        });
        return $rule;
    }

    public static function passwordVerification(string $errorMessage): Rule
    {
        return new Rule(function ($password) {
            $user = Passport::getUser();
            if (is_null($user)) {
                return false;
            }
            return Cryptography::verifyHashedPassword($password, $user->authentication->password_hash);
        }, $errorMessage);
    }

    public static function usernameAvailable(?string $currentUser = null): Rule
    {
        return new Rule(function ($value) use ($currentUser) {
            if ($currentUser == $value) {
                return true;
            }
            return !new UserBroker()->usernameExists($value);
        }, localize("accounts.errors.username_unavailable"));
    }

    public static function emailAvailable(?string $currentEmail = null): Rule
    {
        return new Rule(function ($value) use ($currentEmail) {
            if ($currentEmail == $value) {
                return true;
            }
            return !new UserBroker()->emailExists($value);
        }, localize("accounts.errors.email_unavailable"));
    }
}
