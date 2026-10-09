<?php

namespace App\Support\Constants;

/**
 * = Mes.Common.Constants.ErrorMessageKeys. Giá trị = câu tiếng Anh = khóa dịch trong lang/vi.json.
 * Service trả OperationResult::failure(ErrorMessageKeys::X) thay vì ném exception.
 */
final class ErrorMessageKeys
{
    public const InvalidLoginCredential = 'Invalid user name, email or password.';
    public const AccountTemporarilyLocked = 'Your account is temporarily locked. Please try again after {0} minute(s).';
    public const AccountLockedByAdministrator = 'Your account has been locked. Please contact the administrator.';
    public const AccountDisabled = 'Your account has been disabled.';
    public const EmailNotVerified = 'Your email address has not been verified yet. Please check your mailbox.';

    public const PasswordTooShort = 'Password must be at least {0} characters.';
    public const PasswordRequiresLetterAndDigit = 'Password must contain both letters and digits.';
    public const CurrentPasswordIncorrect = 'Current password is incorrect.';
    public const NewPasswordSameAsCurrent = 'New password must be different from the current password.';
    public const FullNameRequired = 'Full name is required (maximum 150 characters).';

    public const RecordNotFound = 'The record does not exist or has been deleted.';
    public const RoleCodeAlreadyTaken = 'This role code is already taken.';
    public const SystemRoleCannotBeModified = 'System roles cannot be deleted or have their code changed.';
    public const CannotRemoveOwnAdministratorRole = 'You cannot remove the administrator role from your own account.';
    public const CannotLockOwnAccount = 'You cannot lock, disable or delete your own account.';

    public const MenuCodeAlreadyTaken = 'This menu code is already taken.';
    public const MenuHasChildren = 'This menu still has child menus. Move or delete them first.';
    public const MenuParentInvalid = 'The selected parent menu is invalid.';
    public const MenuPageRequiresUrl = 'A page menu must have a URL.';
    public const ModuleCannotHaveParent = 'A module menu cannot have a parent.';
    public const NonModuleRequiresParent = 'Group and page menus must have a parent.';
    public const ReceiptCodeAlreadyTaken = 'This receipt code is already taken.';
}
