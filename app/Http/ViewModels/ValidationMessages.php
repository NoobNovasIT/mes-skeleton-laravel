<?php

namespace App\Http\ViewModels;

/**
 * = Mes.Web/Models/ValidationMessages.cs. Câu tiếng Anh = khóa dịch; {0} = tên field, {1}, {2} = tham số.
 * laravelMessages() đổi {0} / {1} sang placeholder :attribute / :max của Laravel Validator.
 */
final class ValidationMessages
{
    public const Required = '{0} is required.';
    public const MaximumLength = '{0} must not exceed {1} characters.';
    public const CodeFormat = '{0} may contain only uppercase letters, digits and underscore.';
    public const PasswordMismatch = 'The password confirmation does not match.';
    public const NumberRange = '{0} must be between {1} and {2}.';
    public const DateInvalid = '{0} is not a valid date.';

    /** @return array<string, string> */
    public static function laravelMessages(): array
    {
        return [
            'required' => self::toLaravel(self::Required, ['{0}' => ':attribute']),
            'max.string' => self::toLaravel(self::MaximumLength, ['{0}' => ':attribute', '{1}' => ':max']),
            'regex' => self::toLaravel(self::CodeFormat, ['{0}' => ':attribute']),
            'same' => localize(self::PasswordMismatch),
            'between.numeric' => self::toLaravel(self::NumberRange, ['{0}' => ':attribute', '{1}' => ':min', '{2}' => ':max']),
            'numeric' => self::toLaravel(self::NumberRange, ['{0}' => ':attribute', '{1}' => '0.001', '{2}' => '1000000']),
            'date' => self::toLaravel(self::DateInvalid, ['{0}' => ':attribute']),
        ];
    }

    /** @param array<string, string> $placeholderMap */
    private static function toLaravel(string $messageKey, array $placeholderMap): string
    {
        return strtr(localize($messageKey), $placeholderMap);
    }
}
