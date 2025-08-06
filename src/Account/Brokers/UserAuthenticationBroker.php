<?php namespace Pulsar\Account\Brokers;

use Pulsar\Account\Api\PwnedApi;
use Pulsar\Account\Entities\User;
use Pulsar\Account\Utilities;
use stdClass;
use Zephyrus\Database\DatabaseBroker;
use Zephyrus\Exceptions\HttpRequesterException;
use Zephyrus\Security\Cryptography;

class UserAuthenticationBroker extends DatabaseBroker
{
    /**
     * Finds the user corresponding to the given username and password combinaison. Returns null if the combinaison
     * does not exist.
     *
     * @param string $username
     * @param string $password
     * @return stdClass|null
     */
    public function findByAuthentication(string $username, string $password): ?stdClass
    {
        $sql = "SELECT * 
                  FROM pulsar.user_authentication 
                 WHERE username = ? AND password_hash IS NOT NULL";
        $user = $this->selectSingle($sql, [$username]);
        if (is_null($user) || !Cryptography::verifyHashedPassword($password, $user->password_hash)) {
            return null;
        }
        $this->checkCompromisedPassword($user->id, $password);
        return new UserBroker()->findById($user->id);
    }

    public function findByActivationCode(string $activationCode): ?stdClass
    {
        $sql = "SELECT * 
                  FROM pulsar.user_authentication 
                 WHERE activation = ?";
        $user = $this->selectSingle($sql, [$activationCode]);
        if (is_null($user)) {
            return null;
        }
        return new UserBroker()->findById($user->id);
    }

    public function findByOauthUid(string $provider, string $uid): ?stdClass
    {
        $sql = "SELECT * 
                  FROM pulsar.user_authentication 
                 WHERE oauth_provider = ? 
                   AND oauth_uid = ?";
        $user = $this->selectSingle($sql, [$provider, $userId]);
        if (is_null($user)) {
            return null;
        }
        return new UserBroker()->findById($user->id);
    }

    /**
     * Used when the user has MFA method enabled and is unable to use its device for some reason. The user can use one
     * of his recovery codes combined with its password to enter the application. Each code can be used only once.
     *
     * @param string $code
     * @return stdClass|null
     */
    public function findByRecoveryCode(string $code): ?stdClass
    {
        $recoveryCode = $this->selectSingle("SELECT * FROM pulsar.user_recovery_code WHERE code = ? AND used_date IS NULL", [$code]);
        if (is_null($recoveryCode)) {
            return null;
        }
        $sql = 'UPDATE pulsar.user_recovery_code SET used_date = now() WHERE id = ?';
        $this->query($sql, [$recoveryCode->recovery_code_id]);
        return new UserBroker()->findById($recoveryCode->user_id);
    }

    /**
     * Used when the user has checked the "remember me" feature allowing for an automated login using the identifier
     * and special validator included in the user's cookie.
     *
     * @param string $identifier
     * @param string $validator
     * @return stdClass|null
     */
    public function findByAuthenticationToken(string $identifier, string $validator): ?stdClass
    {
        $sql = 'SELECT * FROM pulsar.user_remember_token WHERE identifier = ?';
        $token = $this->selectSingle($sql, [$identifier]);
        if (is_null($token) || !Cryptography::verifyHashedPassword($validator, $token->validation)) {
            return null;
        }
        $sql = 'UPDATE pulsar.user_remember_token SET access = now() WHERE id = ?';
        $this->query($sql, [$token->id]);
        $user = new UserBroker()->findById($token->user_id);
        $user->matching_token = $token; // TODO: ???
        return $user;
    }

    public function checkCompromisedPassword(int $userId, string $password): void
    {
        $compromised = false;
        $config = Utilities::getPwnedConfiguration();
        if ($config->isEnabled()) {
            try {
                $compromised = PwnedApi::findBreachCount($password) >= $config->getTolerance();
            } catch (HttpRequesterException $e) {
                // Ignore HTTP errors to avoid crash caused by unavailability of service.
            }
        }
        parent::query("UPDATE pulsar.user_authentication SET password_compromised = ? WHERE id = ?", [$compromised, $userId]);
    }

    public function updatePassword(User $user, string $newPassword): void
    {
        $this->deleteAllMfaMethods($user->id);
        $this->deleteAllRecovery($user->id);
        $this->deleteAllRememberMe($user->id);
        $hashedPassword = Cryptography::hashPassword($newPassword);
        $sql = "UPDATE pulsar.user_authentication 
                   SET password_hash = ?, 
                       validator = ?, 
                       grace_secret = null, 
                       password_compromised = false,
                       password_reset = false 
                 WHERE id = ?";
        $this->query($sql, [
            $hashedPassword,
            Cryptography::randomString(64),
            $user->id
        ]);
    }

    public function resetPassword(User $user): string
    {
        $this->deleteAllMfaMethods($user->id);
        $this->deleteAllRecovery($user->id);
        $this->deleteAllRememberMe($user->id);
        $newPassword = Cryptography::randomString(16);
        $passwordHash = Cryptography::hashPassword($newPassword);
        $sql = "UPDATE pulsar.user_authentication 
                   SET password_hash = ?,
                       password_reset = true,
                       grace_secret = NULL
                 WHERE id = ?";
        $this->query($sql, [
            $passwordHash,
            $user->id
        ]);
        return $newPassword;
    }

    public function activate(User $user): void
    {
        $sql = "UPDATE pulsar.user_authentication 
                   SET activation = NULL
                 WHERE id = ?";
        $this->query($sql, [
            $user->id
        ]);
    }

    /**
     * Creates the authentication part of the user. Meaning the user will have access to the application. Returns the
     * required activation code for the email.
     *
     * @param int $userId
     * @param stdClass $new
     * @return string
     */
    public function insert(int $userId, stdClass $new): string
    {
        $activation = Cryptography::randomString(64);
        $hashedPassword = null;
        if ($new->password) {
            $hashedPassword = Cryptography::hashPassword($new->password);
        }
        $sql = "INSERT INTO pulsar.user_authentication(username, validator, password_hash, activation, id) 
                VALUES(?, ?, ?, ?, ?)";
        $this->query($sql, [
            $new->username ?? $new->email,
            Cryptography::randomString(64),
            $hashedPassword,
            $activation,
            $userId
        ]);
        return $activation;
    }

    public function insertFromGitHub(int $userId, stdClass $new): void
    {
        $sql = "INSERT INTO pulsar.user_authentication(username, validator, password_hash, activation, id, 
                                       oauth_provider, oauth_uid, oauth_access_token) 
                VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
        $this->query($sql, [
            $new->email,
            Cryptography::randomString(64),
            null,
            null,
            $userId,
            'github',
            $new->id,
            $new->access_token
        ]);
    }

    public function update(User $old, stdClass $user): void
    {
        $sql = "UPDATE pulsar.user_authentication 
                   SET username = ?
                 WHERE id = ?";
        $this->query($sql, [
            $user->username,
            $old->id
        ]);
    }

    public function elevate(User $user): void
    {
        $sql = "UPDATE pulsar.user_authentication 
                   SET superuser = true
                 WHERE id = ?";
        $this->query($sql, [
            $user->id
        ]);
    }

    public function downgrade(User $user): void
    {
        $sql = "UPDATE pulsar.user_authentication 
                   SET superuser = false
                 WHERE id = ?";
        $this->query($sql, [
            $user->id
        ]);
    }

    public function updateLastConnection(int $userId): void
    {
        parent::query("UPDATE pulsar.user_authentication SET last_connection = now() WHERE id = ?", [
            $userId
        ]);
    }

    public function deleteAllRememberMe(int $userId): void
    {
        $this->query("DELETE FROM pulsar.user_remember_token WHERE id = ?", [$userId]);
    }

    public function deleteMfaGrace(int $userId): void
    {
        $this->query("UPDATE pulsar.user_authentication SET grace_secret = NULL WHERE id = ?", [$userId]);
    }

    public function updateGraceSecret(int $userId, string $secret): void
    {
        $this->query("UPDATE pulsar.user_authentication SET grace_secret = ? WHERE id = ?", [$secret, $userId]);
    }

    public function toggleLock(User $user): void
    {
        parent::query("UPDATE pulsar.user_authentication SET locked = NOT locked WHERE id = ?", [
            $user->id
        ]);
    }

    private function deleteAllMfaMethods(int $userId): void
    {
        $sql = "DELETE FROM pulsar.user_mfa WHERE user_id = ?";
        $this->query($sql, [$userId]);
    }

    private function deleteAllRecovery(int $userId): void
    {
        $sql = "DELETE FROM pulsar.user_recovery_code WHERE user_id = ?";
        $this->query($sql, [$userId]);
    }
}
