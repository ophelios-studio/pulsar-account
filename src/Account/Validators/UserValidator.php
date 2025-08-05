<?php namespace Pulsar\Account\Validators;

use Pulsar\Account\AccountRules;
use Pulsar\Account\Entities\User;
use Zephyrus\Application\Form;
use Zephyrus\Application\Rule;
use Zephyrus\Exceptions\FormException;

class UserValidator
{
    /**
     * Scenario in which an administrator creates an account for someone.
     *
     * @param Form $form
     * @param User|null $currentUser
     * @param bool $usernameAsEmail
     * @return void
     */
    public static function assert(Form $form, ?User $currentUser = null, bool $usernameAsEmail = true): void
    {
        self::assetBase($form, $currentUser);
        $form->field('preferred_locale', [
            Rule::required("La langue par défaut ne doit pas être vide."),
            AccountRules::locale()
        ]);
        if (!$usernameAsEmail) {
            $form->field('username', [
                Rule::required(localize("accounts.errors.username_required")),
                AccountRules::username(),
                AccountRules::usernameAvailable((!is_null($currentUser)) ? $currentUser->username : null)
            ]);
        }
        if (!$form->verify()) {
            throw new FormException($form);
        }
    }

    /**
     * Scenario in which the navigating user creates his own account.
     *
     * @param Form $form
     * @param bool $usernameAsEmail
     * @return void
     */
    public static function assertSignup(Form $form, bool $usernameAsEmail = true): void
    {
        self::assetBase($form);
        $form->field('password', [
            Rule::required(localize("accounts.errors.password_required")),
            Rule::passwordCompliant(localize("accounts.errors.password_not_compliant")),
            AccountRules::passwordBreach()
        ]);
        $form->field('agree', [
            Rule::required(localize("accounts.errors.agree_required"))
        ]);
        if (!$usernameAsEmail) {
            $form->field('username', [
                Rule::required(localize("accounts.errors.username_required")),
                AccountRules::username(),
                AccountRules::usernameAvailable()
            ]);
        }
        if (!$form->verify()) {
            throw new FormException($form);
        }
    }

    public static function assertPasswordUpdate(Form $form, bool $oldPasswordNeeded = true): void
    {
        if ($oldPasswordNeeded) {
            $form->field('old_password', [
                AccountRules::passwordVerification(localize("profile.errors.old_password_invalid"))
            ]);
        }
        $form->field('new_password', [
            Rule::required(localize("profile.errors.new_password_required")),
            Rule::passwordCompliant(localize("profile.errors.new_password_not_compliant")),
            AccountRules::passwordBreach()
        ]);
        $form->field('new_password_confirmation', [
            Rule::sameAs('new_password', localize("profile.errors.new_password_confirmation_failed"))
        ]);
        if (!$form->verify()) {
            Form::removeMemorizedValue('old_password');
            Form::removeMemorizedValue('new_password_confirmation');
            throw new FormException($form);
        }
    }

    public static function assertAvatarUpdate(Form $form): void
    {
        $form->field("avatar", [
            Rule::fileUpload(localize("profile.errors.avatar_invalid"))
        ]);
        // TODO: More validations of images ...
        if (!$form->verify()) {
            throw new FormException($form);
        }
    }

    private static function assetBase(Form $form, ?User $currentUser = null): void
    {
        $form->field('firstname', [
            Rule::required(localize("accounts.errors.firstname_required")),
            Rule::name(localize("accounts.errors.firstname_invalid"))
        ]);
        $form->field('lastname', [
            Rule::required(localize("accounts.errors.lastname_required")),
            Rule::name(localize("accounts.errors.lastname_invalid"))
        ]);
        $form->field('email', [
            Rule::required(localize("accounts.errors.email_required")),
            Rule::email(localize("accounts.errors.email_invalid")),
            AccountRules::emailAvailable($currentUser?->email ?? null)
        ]);
    }
}
