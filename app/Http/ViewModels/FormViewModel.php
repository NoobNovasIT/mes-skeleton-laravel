<?php

namespace App\Http\ViewModels;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFactory;

/**
 * Lớp cha cho ViewModel có form. Thay cho DataAnnotations của ASP.NET:
 *   [Required] [StringLength(30)]  ->  rules(): ['ReceiptCode' => ['required', 'max:30']]
 *   [Display(Name = "Receipt code")] -> displayNames(): ['ReceiptCode' => 'Receipt code']
 *
 * Không dùng FormRequest của Laravel vì FormRequest tự redirect / trả 422 JSON khi lỗi,
 * còn dialog của MES cần server trả lại HTML form để hiện lỗi tại chỗ.
 */
abstract class FormViewModel
{
    /** @return array<string, mixed> */
    abstract protected static function rules(): array;

    /** @return array<string, string> */
    abstract protected static function displayNames(): array;

    /** @param array<string, mixed> $input */
    public static function validator(array $input): Validator
    {
        $localizedDisplayNames = array_map(fn (string $displayName) => localize($displayName), static::displayNames());

        return ValidatorFactory::make($input, static::rules(), ValidationMessages::laravelMessages(), $localizedDisplayNames);
    }
}
