<?php namespace Pulsar\Account;

use Pulsar\Account\Entities\User;
use Zephyrus\Core\Session;
use Zephyrus\Security\Cryptography;

final class Passport
{
    /**
     * Verifies if there is a user currently authenticated in the application.
     *
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        return !is_null(Session::get('user'));
    }

    /**
     * Retrieves the entire instance of the authenticated user (if any). Returns null if no user has been
     * authenticated.
     *
     * @return User|null
     */
    public static function getUser(): ?User
    {
        $user = Session::get('user');
        if (is_null($user)) {
            return null;
        }
        return clone User::build((object) $user);
    }

    /**
     * Shortcut method to retrieve the authenticated user id or null otherwise.
     *
     * @return int|null
     */
    public static function getUserId(): ?int
    {
        $user = self::getUser();
        if (is_null($user)) {
            return null;
        }
        return $user->id;
    }

    public static function isSuperuser(): bool
    {
        $user = self::getUser();
        if (is_null($user)) {
            return false;
        }
        return $user->authentication->superuser;
    }

    /**
     * Verifies if the given password matches the one of the authenticated user. Useful for operation that should be
     * password-protected.
     *
     * @param string $password
     * @return bool
     */
    public static function passwordMatch(string $password): bool
    {
        $user = self::getUser();
        if (is_null($user)) {
            return false;
        }
        return Cryptography::verifyHashedPassword($password, $user->authentication->password_hash);
    }

    /**
     * Registers the given user into session as the Passport-authenticated user.
     *
     * @param User $user
     * @return void
     */
    public static function registerUser(User $user): void
    {
        Session::set('user', $user->getRawData());
    }
}
