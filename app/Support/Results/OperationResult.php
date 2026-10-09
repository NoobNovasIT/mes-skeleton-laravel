<?php

namespace App\Support\Results;

/**
 * = Mes.Common.Results.OperationResult / OperationResult<T>.
 * Lỗi nghiệp vụ dự đoán được (trùng mã, sai mật khẩu...) trả Failure, KHÔNG ném exception.
 * PHP không có generic nên OperationResult<T> gộp chung một class, giá trị nằm ở $value.
 */
final class OperationResult
{
    /** @param array<int, mixed> $errorMessageArguments */
    private function __construct(
        public readonly bool $isSuccess,
        public readonly mixed $value = null,
        public readonly ?string $errorMessageKey = null,
        public readonly array $errorMessageArguments = [],
    ) {
    }

    public static function success(mixed $value = null): self
    {
        return new self(true, $value);
    }

    public static function failure(string $errorMessageKey, mixed ...$errorMessageArguments): self
    {
        return new self(false, null, $errorMessageKey, $errorMessageArguments);
    }

    public function isFailure(): bool
    {
        return ! $this->isSuccess;
    }

    /** Dịch thông báo lỗi sang ngôn ngữ hiện tại (MesControllerBase.LocalizeError bên C#). */
    public function localizedError(): string
    {
        return $this->errorMessageKey === null ? '' : localize($this->errorMessageKey, ...$this->errorMessageArguments);
    }
}
