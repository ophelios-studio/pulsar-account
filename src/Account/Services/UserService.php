<?php namespace Pulsar\Account\Services;

use Pulsar\Account\Api\PwnedApi;
use Pulsar\Account\Brokers\UserAuthenticationBroker;
use Pulsar\Account\Brokers\UserBroker;
use Pulsar\Account\Brokers\UserMfaBroker;
use Pulsar\Account\Brokers\UserSettingBroker;
use Pulsar\Account\Entities\User;
use Pulsar\Account\Entities\UserProfile;
use Pulsar\Account\Passport;
use Pulsar\Account\Utilities;
use Pulsar\Account\Validators\UserValidator;
use Pulsar\Mailer\Mailer;
use Zephyrus\Application\Form;
use Zephyrus\Core\Application;
use Zephyrus\Core\Configuration;
use Zephyrus\Exceptions\FormException;
use stdClass;
use Zephyrus\Exceptions\HttpRequesterException;
use Zephyrus\Security\Cryptography;

class UserService
{
    public static function read(int $userId): ?User
    {
        return User::build(new UserBroker()->findById($userId));
    }

    public static function readByUsername(string $username): ?User
    {
        return User::build(new UserBroker()->findByUsername($username));
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

    public static function signupByGitHub(Form $form, stdClass $gitHubUser, string $accessToken): User
    {
        if (new UserBroker()->emailExists($gitHubUser->email)) {
            $form->addError('email', localize("accounts.errors.email_unavailable"));
            throw new FormException($form);
        }
        UserValidator::assertSignupFromGitHub($form);
        $user = (object) [
            'firstname' => $form->getValue('firstname'),
            'lastname' => $form->getValue('lastname'),
            'email' => $gitHubUser->email,
            'access_token' => $accessToken,
            'uid' => $gitHubUser->id
        ];
        $userId = new UserBroker()->insert($user);
        new UserAuthenticationBroker()->insertFromGitHub($userId, $user);
        new UserSettingBroker(Application::getInstance())->insert($userId, $user);

        $filename = Cryptography::randomString(32) . '.png';
        self::downloadGitHubAvatar($gitHubUser->avatar_url, $filename);

        $user = self::read($userId);
        new UserBroker()->updateAvatar($user, $filename);
        return $user;
    }

    public static function enableEmailMfa(User $user): void
    {
        new UserMfaBroker($user)->insert('email');
        Passport::reloadUser();
    }

    public static function disableEmailMfa(User $user): void
    {
        new UserMfaBroker($user)->delete('email');
        Passport::reloadUser();
    }

    public static function enableOtpMfa(User $user): void
    {
        new UserMfaBroker($user)->insert('otp');
        Passport::reloadUser();
    }

    public static function disableOtpMfa(User $user): void
    {
        new UserMfaBroker($user)->delete('otp');
        Passport::reloadUser();
    }

    public static function setPrimaryMfa(User $user, int $mfaId): void
    {
        $broker = new UserMfaBroker($user);
        if ($broker->exists($mfaId)) {
            $broker->setPrimary($mfaId);
            Passport::reloadUser();
        }
    }

    public static function updatePassword(User $user, Form $form): User
    {
        UserValidator::assertPasswordUpdate($form, false);
        new UserAuthenticationBroker()->updatePassword($user, $form->getValue('new_password'));
        return self::read($user->id);
    }

    public static function updateGraceSecret(User $user, string $secret): void
    {
        new UserAuthenticationBroker()->updateGraceSecret($user->id, $secret);
    }

    public static function resetPassword(Form $form): void
    {
        UserValidator::assertPasswordReset($form);
        $user = self::readByUsername($form->getValue('email'));
        if (!$user->isActivated()) {
            $form->addError('email', localize("accounts.errors.email_not_activated"));
            throw new FormException($form);
        }
        if (!is_null($user->authentication->oauth_provider)) {
            $form->addError('email', localize("accounts.errors.cannot_reset_password_oauth", $user->authentication->oauth_provider));
            throw new FormException($form);
        }
        $newPassword = new UserAuthenticationBroker()->resetPassword($user);
        self::sendPasswordResetEmail($user, $newPassword);
    }

    public static function updateLastConnection(int $userId): void
    {
        new UserAuthenticationBroker()->updateLastConnection($userId);
    }

    public static function isPasswordBreach(string $password): bool
    {
        $compromised = false;
        $config = Utilities::getPwnedConfiguration();
        if ($config->isEnabled()) {
            try {
                $breaches = PwnedApi::findBreachCount($password);
                $compromised = $breaches >= $config->getTolerance();
            } catch (HttpRequesterException $e) {
                // Ignore HTTP errors to avoid crash caused by unavailability of service.
            }
        }
        return $compromised;
    }

    private static function sendActivationEmail(User $user, string $activationCode): void
    {
        $baseUrl = Application::getInstance()->getRequest()->getUrl()->getBaseUrl();
        $mailer = new Mailer();
        $mailer->setSubject(localize("accounts.emails.activation.subject", ['fullname' => $user->fullname]));
        $mailer->setTemplate("pulsar/emails/activation", [
            'url' => $baseUrl . "/signup-activation/" . $activationCode,
            'user' => $user,
            'contact_email' => Configuration::getApplication('contact_email')
        ], $user->setting->preferred_locale);
        $mailer->addInlineAttachment(ROOT_DIR . '/public/assets' . localize("emails.logo"), "logo.png");
        $mailer->addRecipient($user->email, $user->fullname);
        $mailer->send();
    }

    private static function sendPasswordResetEmail(User $user, string $newPassword): void
    {
        $mailer = new Mailer();
        $mailer->setSubject(localize("accounts.emails.reset.subject"));
        $mailer->setTemplate("pulsar/emails/reset", [
            'user' => $user,
            'new_password' => $newPassword,
            'contact_email' => Configuration::getApplication('contact_email')
        ], $user->setting->preferred_locale);
        $mailer->addInlineAttachment(ROOT_DIR . '/public/assets' . localize("emails.logo"), "logo.png");
        $mailer->addRecipient($user->email, $user->fullname);
        $mailer->send();
    }

    private static function downloadGitHubAvatar(string $avatarUrl, string $filename): bool
    {
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: MyApp/1.0\r\n",
                'timeout' => 10 // Timeout after 10 seconds if the download fails
            ]
        ]);
        $avatarContent = file_get_contents($avatarUrl, false, $context);
        if ($avatarContent === false) {
            return false;
        }
        $saved = file_put_contents(ROOT_DIR . '/public/assets/images/avatars/' . $filename, $avatarContent);
        return $saved !== false;
    }
}
