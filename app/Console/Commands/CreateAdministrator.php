<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\UserAccount;
use App\Models\UserAccountRole;
use App\Services\Security\PasswordHasher;
use App\Services\Security\PasswordPolicy;
use App\Support\Constants\RoleCodes;
use App\Support\Enums\AccountStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * = InitialDataService (InitialAdministrator trong appsettings) của bản ASP.NET.
 * Tạo tài khoản Admin đầu tiên cho database mới: Active, đã xác thực email, Role ADMIN,
 * bắt đổi mật khẩu ở lần đăng nhập đầu.
 *
 *   php artisan mes:create-admin admin admin@example.com
 */
final class CreateAdministrator extends Command
{
    protected $signature = 'mes:create-admin {userName} {email} {--full-name=System Administrator}';

    protected $description = 'Tạo tài khoản Admin đầu tiên (Role ADMIN, bắt đổi mật khẩu lần đầu)';

    public function handle(PasswordHasher $passwordHasher): int
    {
        $userName = trim($this->argument('userName'));
        $email = strtolower(trim($this->argument('email')));

        if (UserAccount::query()->where('UserName', $userName)->orWhere('Email', $email)->exists()) {
            $this->error('UserName hoặc Email đã tồn tại.');

            return self::FAILURE;
        }

        $administratorRole = Role::query()->where('RoleCode', RoleCodes::Administrator)->first();
        if ($administratorRole === null) {
            $this->error('Chưa có Role ADMIN - chạy trước: php artisan mes:run-sql database/MASTERDATA_SQL');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Mật khẩu tạm (ít nhất 8 ký tự, có chữ và số)');
        $passwordPolicyResult = PasswordPolicy::validate($password, 8);
        if ($passwordPolicyResult->isFailure()) {
            $this->error($passwordPolicyResult->localizedError());

            return self::FAILURE;
        }

        DB::transaction(function () use ($userName, $email, $password, $passwordHasher, $administratorRole) {
            $userAccount = UserAccount::query()->create([
                'UserName' => $userName,
                'Email' => $email,
                'FullName' => $this->option('full-name'),
                'PasswordHash' => $passwordHasher->hash($password),
                'IsEmailVerified' => true,
                'AccountStatus' => AccountStatus::Active,
                'MustChangePassword' => true,
                'SecurityStamp' => strtoupper((string) Str::uuid()),
            ]);

            UserAccountRole::query()->create(['UserAccountId' => $userAccount->Id, 'RoleId' => $administratorRole->Id]);
        });

        $this->info("Đã tạo {$userName}. Đăng nhập lần đầu sẽ bị yêu cầu đổi mật khẩu.");

        return self::SUCCESS;
    }
}
