<?php namespace Pulsar\Account\Services;

use Pulsar\Account\Brokers\UserAuthenticationBroker;
use Pulsar\Account\Brokers\UserBroker;
use Pulsar\Account\Brokers\UserSettingBroker;
use Pulsar\Account\Entities\User;
use Pulsar\Account\Entities\UserProfile;
use Pulsar\Account\Validators\UserValidator;
use Pulsar\Mailer\Mailer;
use Zephyrus\Application\Form;
use Zephyrus\Core\Application;
use Zephyrus\Core\Configuration;

class UserService
{
    public static function read(int $userId): ?User
    {
        return User::build(new UserBroker()->findById($userId));
    }

    public static function readProfile(int $userId): ?UserProfile
    {
        return UserProfile::build(new UserBroker()->findProfileById($userId));
    }

    public static function signup(Form $form): User
    {
        UserValidator::assertSignup($form);
        $userId = new UserBroker()->insert($form->buildObject());
        $activationCode = new UserAuthenticationBroker()->insert($userId, $form->buildObject());
        new UserSettingBroker(Application::getInstance())->insert($userId, $form->buildObject());
        $new = self::read($userId);
        self::sendActivationEmail($new, $activationCode);
        return $new;
    }

    public static function updateLastConnection(int $userId): void
    {
        new UserAuthenticationBroker()->updateLastConnection($userId);
    }

    private static function sendActivationEmail(User $user, string $activationCode): void
    {
        $baseUrl = Application::getInstance()->getRequest()->getUrl()->getBaseUrl();
        $mailer = new Mailer();
        $mailer->setSubject(localize("accounts.emails.activation.subject", ['fullname' => $user->fullname]));
        $mailer->setTemplate("emails/activation", [
            'url' => $baseUrl . "/signup-activation/" . $activationCode,
            'user' => $user,
            'contact_email' => Configuration::getApplication('contact_email')
        ], $user->setting->preferred_locale);
        $mailer->addInlineAttachment(ROOT_DIR . '/public/assets' . localize("emails.logo"), "logo.png");
        $mailer->addRecipient($user->email, $user->fullname);
        $mailer->send();
    }
}
