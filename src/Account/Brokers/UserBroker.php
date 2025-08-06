<?php namespace Pulsar\Account\Brokers;

use Pulsar\Account\Entities\User;
use stdClass;
use Zephyrus\Database\DatabaseBroker;
use Zephyrus\Utilities\FileSystem\File;

class UserBroker extends DatabaseBroker
{
    public function findById(int $userId): stdClass
    {
        $sql = "SELECT * FROM pulsar.view_user WHERE id = ?";
        return $this->selectSingle($sql, [$userId]);
    }

    public function findByUsername(string $username): stdClass
    {
        $sql = "SELECT * FROM pulsar.view_user WHERE username = ?";
        return $this->selectSingle($sql, [$username]);
    }

    public function findProfileById(int $userId): stdClass
    {
        $sql = "SELECT * FROM pulsar.view_user_profile WHERE id = ?";
        return $this->selectSingle($sql, [$userId]);
    }

    public function emailExists(string $email): bool
    {
        $sql = "SELECT COUNT(id) as n FROM pulsar.user_profile WHERE email = ?";
        return $this->selectSingle($sql, [$email])->n > 0;
    }

    public function usernameExists(string $username): bool
    {
        $sql = "SELECT COUNT(id) as n FROM pulsar.user_authentication WHERE username = ?";
        return $this->selectSingle($sql, [$username])->n > 0;
    }

    public function updateAvatar(User $user, string $avatarPath): void
    {
        $this->deleteAvatar($user);
        $this->query("UPDATE pulsar.user_profile SET avatar = ? WHERE id = ?", [
            $avatarPath,
            $user->id
        ]);
    }

    public function insert(stdClass $new): int
    {
        $sql = "INSERT INTO pulsar.user_profile(firstname, lastname, email) VALUES (?, ?, ?) RETURNING id";
        return $this->query($sql, [
            $new->firstname,
            $new->lastname,
            $new->email
        ])->id;
    }

    /**
     * Proceeds to update the "user profile" table related to the given user identifier.
     *
     * @param User $old
     * @param stdClass $new
     */
    public function update(User $old, stdClass $new): void
    {
        $sql = "UPDATE pulsar.user_profile 
                   SET firstname = ?, 
                       lastname = ?, 
                       email = ?
                 WHERE id = ?";
        $this->query($sql, [
            $new->firstname,
            $new->lastname,
            $new->email,
            $old->id
        ]);
    }

    /**
     * Should be updated every request the authenticated user does to keep its "online" status active.
     *
     * @param int $userId
     * @return void
     */
    public function updateLastActivity(int $userId): void
    {
        $this->query("UPDATE pulsar.user_profile SET last_activity = now() WHERE id = ?", [
            $userId
        ]);
    }

    /**
     * Deletes a single user including its dependencies (authentication table row and other linked elements if
     * available). May restrict depending on how foreign keys are defined.
     *
     * @param User $old
     * @return int
     */
    public function delete(User $old): int
    {
        $this->deleteAvatar($old);
        $this->query('DELETE FROM pulsar.user_profile WHERE id = ?', [$old->id]);
        return $this->getLastAffectedCount();
    }

    private function deleteAvatar(User $user): void
    {
        if (!is_null($user->avatar)) {
            $path = ROOT_DIR . "/public/assets/images/avatars/$user->avatar";
            if (File::exists($path)) {
                new File($path)->remove();
            }
        }
    }
}
