<?php namespace Pulsar\Account;

use Pulsar\Account\Entities\User;
use Pulsar\Account\Services\UserService;
use Pulsar\Mailer\Mailer;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use Zephyrus\Application\Configuration;
use Zephyrus\Core\Application;
use Zephyrus\Core\Session;
use Zephyrus\Network\Cookie;
use Zephyrus\Security\Cryptography;

class MultiFactor
{
    private const int DEFAULT_EXPIRATION = 2 * 60; // 2 minutes
    private User $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function hasGraceTime(): bool
    {
        $graceToken = Application::getInstance()->getRequest()->getCookieJar()->get('grace_time');
        return !is_null($graceToken) && $graceToken == $this->user->authentication->grace_secret;
    }

    public function activateGraceTime(): void
    {
        $secret = Cryptography::randomString(16);
        UserService::updateGraceSecret($this->user, $secret);
        $cookie = new Cookie("grace_time", $secret);
        $cookie->setLifetime(Cookie::DURATION_DAY * 20);
        $cookie->send();
    }

    public function hasExpired(): bool
    {
        return is_null(Session::get('multi_factor_expiration'));
    }

    public function verifyEmailCode(string $authenticationCode): bool
    {
        $code = Session::get('email_factor_code');
        if (is_null($code)) {
            return false;
        }
        Session::remove('email_factor_code');
        return $authenticationCode == $code;
    }

    public function verifyAuthenticatorCode(string $authenticationCode): bool
    {
        $secret = Session::get('authenticator_factor_secret');
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
        return $tfa->verifyCode($secret, $authenticationCode);
    }

    public function initiateEmail(): void
    {
        $authenticationCode = Cryptography::randomInt(102314, 999999);
        Session::set('email_factor_code', $authenticationCode);
        Session::set('multi_factor_expiration', time() + self::DEFAULT_EXPIRATION);
        $this->sendPasswordAuthenticationEmail($authenticationCode);
    }

    public function initiateAuthenticator(): void
    {
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
        $otp = $this->user->authentication->getMfa('otp');
        $secret = (!$otp) ? $tfa->createSecret() : $otp->secret;
        $label = Configuration::getApplication('project') . ': ' . Passport::getUser()->username;
        $imageUrl = "";
        if (!$otp) {
            $imageUrl = $tfa->getQRCodeImageAsDataUri($label, $secret);
        }
        Session::set('authenticator_factor_secret', $secret);
        Session::set('authenticator_factor_image', $imageUrl);
    }

    private function sendPasswordAuthenticationEmail(string $authenticationCode): void
    {
        $mailer = new Mailer();
        $mailer->setSubject(localize("accounts.emails.authentication.title"));
        $mailer->setTemplate("pulsar/emails/authentication", [
            'authentication_code' => $authenticationCode,
            'date_time' => date(FORMAT_DATE_TIME),
            'ip_address' => Application::getInstance()->getRequest()->getClientIp(),
            'user' => $this->user
        ]);
        $mailer->addInlineAttachment(ROOT_DIR . '/public/assets' . localize("emails.logo"), "logo.png");
        $mailer->addRecipient($this->user->email, $this->user->fullname);
        $mailer->send();
    }
}
