<?php

namespace App\Services\Security;

/**
 * = BCryptPasswordHasher.cs (BCrypt.Net-Next). Dùng chung bảng UserAccount với bản ASP.NET nên hash phải đọc được ở cả hai:
 *   - password_verify() của PHP đọc được hash "$2a$" do BCrypt.Net tạo.
 *   - PHP tạo hash "$2y$"; đổi tiền tố sang "$2a$" (cùng thuật toán) để bản C# chắc chắn đọc được.
 */
final class PasswordHasher
{
    private const WorkFactor = 11;

    public function hash(string $plainPassword): string
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => self::WorkFactor]);

        return '$2a$'.substr($hash, 4);
    }

    public function verify(string $plainPassword, string $passwordHash): bool
    {
        return password_verify($plainPassword, $passwordHash);
    }

    /** Không tìm thấy tài khoản vẫn tốn thời gian như kiểm tra thật (chống dò tài khoản theo thời gian phản hồi). */
    public function simulateVerification(string $plainPassword): void
    {
        password_verify($plainPassword, $this->hash('simulated-password'));
    }
}
