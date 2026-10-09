<?php

namespace App\Services\Accounts;

use App\Models\UserAccount;
use App\Services\AuditLogs\AuditLogWriter;
use App\Services\Caching\UserScopedCache;
use App\Services\Security\PasswordHasher;
use App\Services\Security\PasswordPolicy;
use App\Services\SystemSettings\SystemSettingService;
use App\Support\Constants\AuditActionTypes;
use App\Support\Constants\ErrorMessageKeys;
use App\Support\Enums\AccountStatus;
use App\Support\Results\OperationResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * = Mes.Service/Accounts/AccountService.cs - phần đăng nhập, đổi mật khẩu, hồ sơ, trạng thái phiên.
 * (Đăng ký / xác thực email / quên mật khẩu chưa port - cần gửi email.)
 */
final class AccountService
{
    public function __construct(
        private readonly PasswordHasher $passwordHasher,
        private readonly SystemSettingService $systemSettingService,
        private readonly UserScopedCache $userScopedCache,
    ) {
    }

    /**
     * Thứ tự kiểm tra giữ đúng bản C#: tìm user -> khóa tạm -> mật khẩu -> trạng thái.
     * Trạng thái (Locked / chưa xác thực email) chỉ báo SAU khi mật khẩu đúng để người lạ không dò được.
     * Thành công: OperationResult với value = UserAccount.
     */
    public function validateCredential(string $loginName, string $password): OperationResult
    {
        $normalizedLoginName = trim($loginName);
        $userAccount = UserAccount::query()
            ->where(fn ($query) => $query->where('UserName', $normalizedLoginName)->orWhere('Email', $normalizedLoginName))
            ->first();

        if ($userAccount === null) {
            $this->passwordHasher->simulateVerification($password);
            AuditLogWriter::addForUser(null, $normalizedLoginName, AuditActionTypes::LoginFailed, 'Unknown login name');

            return OperationResult::failure(ErrorMessageKeys::InvalidLoginCredential);
        }

        if ($userAccount->isTemporarilyLocked()) {
            $remainingMinute = max(1, (int) ceil(now()->diffInSeconds($userAccount->LockoutEndAt) / 60));

            return OperationResult::failure(ErrorMessageKeys::AccountTemporarilyLocked, $remainingMinute);
        }

        if (! $this->passwordHasher->verify($password, $userAccount->PasswordHash)) {
            return $this->registerFailedLogin($userAccount);
        }

        $accountStatusFailure = match (true) {
            $userAccount->AccountStatus === AccountStatus::Locked => ErrorMessageKeys::AccountLockedByAdministrator,
            $userAccount->AccountStatus === AccountStatus::Disabled => ErrorMessageKeys::AccountDisabled,
            ! $userAccount->IsEmailVerified => ErrorMessageKeys::EmailNotVerified,
            default => null,
        };
        if ($accountStatusFailure !== null) {
            AuditLogWriter::addForUser($userAccount->Id, $userAccount->UserName, AuditActionTypes::LoginFailed, $accountStatusFailure);

            return OperationResult::failure($accountStatusFailure);
        }

        DB::transaction(function () use ($userAccount) {
            $userAccount->FailedLoginCount = 0;
            $userAccount->LockoutEndAt = null;
            $userAccount->LastLoginAt = now();
            $userAccount->save();
            AuditLogWriter::addForUser($userAccount->Id, $userAccount->UserName, AuditActionTypes::Login, null);
        });

        return OperationResult::success($userAccount);
    }

    private function registerFailedLogin(UserAccount $userAccount): OperationResult
    {
        $securityPolicy = $this->systemSettingService->getSecurityPolicy();

        $userAccount->FailedLoginCount++;
        $isLockoutTriggered = $userAccount->FailedLoginCount >= $securityPolicy->lockoutMaxFailedAttempt;
        if ($isLockoutTriggered) {
            $userAccount->LockoutEndAt = now()->addMinutes($securityPolicy->lockoutDurationMinute);
            $userAccount->FailedLoginCount = 0;
        }

        DB::transaction(function () use ($userAccount, $isLockoutTriggered, $securityPolicy) {
            $userAccount->save();
            AuditLogWriter::addForUser(
                $userAccount->Id,
                $userAccount->UserName,
                AuditActionTypes::LoginFailed,
                $isLockoutTriggered ? "Wrong password - locked for {$securityPolicy->lockoutDurationMinute} minute(s)" : 'Wrong password');
        });

        return $isLockoutTriggered
            ? OperationResult::failure(ErrorMessageKeys::AccountTemporarilyLocked, $securityPolicy->lockoutDurationMinute)
            : OperationResult::failure(ErrorMessageKeys::InvalidLoginCredential);
    }

    public function recordLogout(): void
    {
        AuditLogWriter::add(AuditActionTypes::Logout, null);
    }

    public function changePassword(int $userAccountId, string $currentPassword, string $newPassword): OperationResult
    {
        $userAccount = UserAccount::query()->find($userAccountId);
        if ($userAccount === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        if (! $this->passwordHasher->verify($currentPassword, $userAccount->PasswordHash)) {
            return OperationResult::failure(ErrorMessageKeys::CurrentPasswordIncorrect);
        }

        if ($currentPassword === $newPassword) {
            return OperationResult::failure(ErrorMessageKeys::NewPasswordSameAsCurrent);
        }

        $passwordPolicyResult = PasswordPolicy::validate($newPassword, $this->systemSettingService->getSecurityPolicy()->passwordMinimumLength);
        if ($passwordPolicyResult->isFailure()) {
            return $passwordPolicyResult;
        }

        DB::transaction(function () use ($userAccount, $newPassword) {
            $this->setNewPassword($userAccount, $newPassword);
            $userAccount->save();
            AuditLogWriter::add(AuditActionTypes::PasswordChanged, null, 'UserAccount', (string) $userAccount->Id);
        });
        $this->userScopedCache->invalidateUser($userAccount->Id);

        return OperationResult::success($userAccount);
    }

    /** SecurityStamp mới -> mọi phiên đăng nhập khác của user bị đẩy ra ở request kế tiếp. */
    public function setNewPassword(UserAccount $userAccount, string $newPassword): void
    {
        $userAccount->PasswordHash = $this->passwordHasher->hash($newPassword);
        $userAccount->MustChangePassword = false;
        $userAccount->FailedLoginCount = 0;
        $userAccount->LockoutEndAt = null;
        $userAccount->SecurityStamp = strtoupper((string) Str::uuid());
    }

    public function getSessionState(int $userAccountId): ?UserSessionStateDto
    {
        return $this->userScopedCache->remember($userAccountId, 'SessionState', function () use ($userAccountId) {
            $userAccount = UserAccount::query()->with('roles')->find($userAccountId);

            return $userAccount === null ? null : new UserSessionStateDto(
                $userAccount->Id,
                $userAccount->UserName,
                $userAccount->FullName,
                strtoupper((string) $userAccount->SecurityStamp),
                $userAccount->AccountStatus,
                $userAccount->MustChangePassword,
                $userAccount->PreferredLanguageCode,
                $userAccount->roleNames(),
            );
        });
    }

    public function getProfile(int $userAccountId): ?UserAccount
    {
        return UserAccount::query()->with('roles')->find($userAccountId);
    }

    public function updateProfile(int $userAccountId, string $fullName, ?string $preferredLanguageCode): OperationResult
    {
        $fullName = trim($fullName);
        if ($fullName === '' || mb_strlen($fullName) > 150) {
            return OperationResult::failure(ErrorMessageKeys::FullNameRequired);
        }

        $userAccount = UserAccount::query()->find($userAccountId);
        if ($userAccount === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        $userAccount->FullName = $fullName;
        $userAccount->PreferredLanguageCode = $preferredLanguageCode;
        DB::transaction(fn () => $userAccount->save());
        $this->userScopedCache->invalidateUser($userAccountId);

        return OperationResult::success();
    }

    public function updatePreferredLanguage(int $userAccountId, string $languageCode): void
    {
        $userAccount = UserAccount::query()->find($userAccountId);
        if ($userAccount === null || $userAccount->PreferredLanguageCode === $languageCode) {
            return;
        }

        $userAccount->PreferredLanguageCode = $languageCode;
        DB::transaction(fn () => $userAccount->save());
        $this->userScopedCache->invalidateUser($userAccountId);
    }
}
