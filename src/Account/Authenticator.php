<?php namespace Pulsar\Account;

use Pulsar\Account\Entities\User;
use Pulsar\Account\Exceptions\AuthenticationBruteForceException;
use Pulsar\Account\Exceptions\AuthenticationDeniedException;
use Pulsar\Account\Exceptions\AuthenticationLockedException;
use Pulsar\Account\Exceptions\AuthenticationNotConfirmedException;
use Pulsar\Account\Exceptions\AuthenticationPasswordCompromisedException;
use Pulsar\Account\Exceptions\AuthenticationPasswordResetException;
use Pulsar\Account\Exceptions\AuthenticationRootException;
use Pulsar\Account\Exceptions\RecognizerException;
use Pulsar\Account\Services\AuthenticationService;
use Pulsar\Account\Services\RememberTokenService;
use Pulsar\Account\Services\UserService;
use Zephyrus\Core\Application;
use Zephyrus\Core\Configuration;
use Zephyrus\Core\Session;

class Authenticator
{
    /**
     * @return void
     * @throws AuthenticationDeniedException
     * @throws AuthenticationLockedException
     * @throws AuthenticationNotConfirmedException
     * @throws AuthenticationRootException
     * @throws AuthenticationBruteForceException
     * @throws AuthenticationPasswordResetException
     * @throws AuthenticationPasswordCompromisedException
     */
    public function login(): void
    {
        $request = Application::getInstance()->getRequest();
        $username = $request->getParameter('username', '');
        $password = $request->getParameter('password', '');
        $configuration = Configuration::getSecurity()->getConfiguration('bruteforce', ['enabled' => true, 'attempts' => 5]);
        if ($configuration['enabled']) {
            $bruteForceProtection = new BruteForceProtection($configuration['attempts']);
            $bruteForceProtection->verify($username);
        }

        $user = AuthenticationService::authenticate($username, $password);

        if (is_null($user)) {
            if ($configuration['enabled']) {
                $bruteForceProtection->mitigate($username);
            }
            throw new AuthenticationDeniedException();
        }
        if (!$user->isActivated()) {
            throw new AuthenticationNotConfirmedException();
        }
        if ($user->isLocked()) {
            throw new AuthenticationLockedException();
        }
        if ($user->id == 1) {
            throw new AuthenticationRootException();
        }
        if ($user->authentication->password_reset) {
            throw new AuthenticationPasswordResetException($user->username);
        }
        if (UserService::isPasswordBreach($password)) {
            throw new AuthenticationPasswordCompromisedException($user->username);
        }
        if ($configuration['enabled']) {
            $bruteForceProtection->clear($username);
        }
        UserService::updateLastConnection($user->id);
        Passport::registerUser($user);
        if (!is_null($request->getParameter('remember'))) {
            $this->remember($user);
        }
    }

    public function logout(): void
    {
        $remember = Remember::recognize();
        if (!is_null($remember)) {
            $identifier = $remember->getIdentifier();
            $token = RememberTokenService::readByIdentifier(Passport::getUserId(), $identifier);
            if (!is_null($token)) {
                RememberTokenService::remove(Passport::getUserId(), $token->id);
            }
            Remember::destroy();
        }
        Session::destroy();
    }

    /**
     * @throws RecognizerException
     */
    public function automatedLogin(): bool
    {
        try {
            $remember = Remember::recognize();
            if (is_null($remember)) { // No cookie found
                return false;
            }
        } catch (RecognizerException $e) {
            Remember::destroy();
            throw $e;
        }

        $user = AuthenticationService::authenticateByToken($remember->getIdentifier(), $remember->getValidator());
        if (is_null($user)) {
            Remember::destroy();
            return false;
        }

        try {
            $remember->validateAuthenticationToken($user->matching_token);
        } catch (RecognizerException $e) {
            Remember::destroy();
            RememberTokenService::remove($user->id, $user->matching_token->id);
            throw $e;
        }

        $remember->regenerateSequence();
        RememberTokenService::updateSequence($user->id, $user->matching_token->id, $remember->getSequence());
        $remember->sendSequenceCookie(); // resend new sequence cookie
        UserService::updateLastConnection($user->id);
        Passport::registerUser($user);
        return true;
    }

    private function remember(User $user): void
    {
        RememberTokenService::remember($user);
    }
}
