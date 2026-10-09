<?php

namespace App\Services\Caching;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * = Mes.Service/Caching/UserScopedCache.cs: cache theo user (quyền, menu, trạng thái phiên), hết hạn 30 phút.
 *
 * PHP không giữ bộ nhớ giữa các request (mỗi request chạy lại từ đầu), nên dùng Cache của Laravel
 * (file trong storage/framework/cache). Xóa cache bằng cách tăng "phiên bản":
 *   invalidateUser(5)      -> mọi key của user 5 đổi tên -> lần đọc sau tự tính lại
 *   invalidateAllUsers()   -> mọi key của mọi user đổi tên
 */
final class UserScopedCache
{
    private const DurationMinute = 30;

    public function remember(int $userAccountId, string $cacheKeyName, Closure $valueFactory): mixed
    {
        $cacheKey = sprintf(
            'mes:user:%d:v%d:g%d:%s',
            $userAccountId,
            $this->readVersion('mes:cache-version:user:'.$userAccountId),
            $this->readVersion('mes:cache-version:global'),
            $cacheKeyName);

        return Cache::remember($cacheKey, now()->addMinutes(self::DurationMinute), $valueFactory);
    }

    public function invalidateUser(int $userAccountId): void
    {
        $this->increaseVersion('mes:cache-version:user:'.$userAccountId);
    }

    public function invalidateAllUsers(): void
    {
        $this->increaseVersion('mes:cache-version:global');
    }

    private function readVersion(string $versionKey): int
    {
        return (int) Cache::get($versionKey, 0);
    }

    private function increaseVersion(string $versionKey): void
    {
        Cache::forever($versionKey, $this->readVersion($versionKey) + 1);
    }
}
