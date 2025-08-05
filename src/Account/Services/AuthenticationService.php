<?php namespace Pulsar\Account\Services;

use Pulsar\Account\Brokers\UserAuthenticationBroker;
use Pulsar\Account\Entities\User;
use Zephyrus\Security\Cryptography;

class AuthenticationService
{
    /**
     * Fetches the user object with a traditional method of user and password.
     *
     * @param string $username
     * @param string $password
     * @return ?User
     */
    public static function authenticate(string $username, string $password): ?User
    {
        return User::build(new UserAuthenticationBroker()->findByAuthentication($username, $password));
    }

    /**
     * Tries to find the user matching the given activation code. Should be used when a user is newly created or the
     * password has been reset.
     *
     * @param string $code
     * @return ?User
     */
    public static function authenticateByActivationCode(string $code): ?User
    {
        return User::build(new UserAuthenticationBroker()->findByActivationCode($code));
    }

    /**
     * Fetches the user object from a recovery code (MFA may be impossible to use for the user).
     *
     * @param string $code
     * @param string $password
     * @return ?User
     */
    public static function authenticateByRecoveryCode(string $code, string $password): ?User
    {
        $user = User::build(new UserAuthenticationBroker()->findByRecoveryCode($code));
        if (is_null($user) || !Cryptography::verifyHashedPassword($password, $user->authentication->password_hash)) {
            return null;
        }
        return $user;
    }

    /**
     * Fetches the user object from an automated authentication (with the remember me token).
     *
     * @param string $identifier
     * @param string $validator
     * @return ?User
     */
    public static function authenticateByToken(string $identifier, string $validator): ?User
    {
        return User::build(new UserAuthenticationBroker()->findByAuthenticationToken($identifier, $validator));
    }

    public static function activate(User $user): void
    {
        new UserAuthenticationBroker()->activate($user);
    }
}
