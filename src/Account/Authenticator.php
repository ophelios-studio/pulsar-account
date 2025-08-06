<?php namespace Pulsar\Account;

use Pulsar\Account\Exceptions\AuthenticationBruteForceException;
use Pulsar\Account\Exceptions\AuthenticationDeniedException;
use Pulsar\Account\Exceptions\AuthenticationLockedException;
use Pulsar\Account\Exceptions\AuthenticationNotConfirmedException;
use Pulsar\Account\Exceptions\AuthenticationPasswordResetException;
use Pulsar\Account\Exceptions\AuthenticationRootException;
use Pulsar\Account\Services\AuthenticationService;
use Pulsar\Account\Services\UserService;
use Zephyrus\Core\Application;
use Zephyrus\Core\Configuration;

class Authenticator
{
    /**
     * @return void
     * @throws AuthenticationDeniedException
     * @throws AuthenticationLockedException
     * @throws AuthenticationNotConfirmedException
     * @throws AuthenticationRootException
     * @throws AuthenticationBruteForceException
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
        if ($configuration['enabled']) {
            $bruteForceProtection->clear($username);
        }
        UserService::updateLastConnection($user->id);
        Passport::registerUser($user);
        if (!is_null($request->getParameter('remember'))) {
            $this->remember();
        }
    }

    private function remember(): void
    {
        // TODO:
    }
}
