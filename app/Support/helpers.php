<?php

/*
 | Hàm dùng chung cho cả PHP và Blade (nạp qua "autoload.files" trong composer.json).
 | Tương đương IStringLocalizer + _ViewStart.cshtml bên ASP.NET.
 */

if (! function_exists('localize')) {
    /**
     * Khóa = câu tiếng Anh (giống Localizer["..."]). Bản dịch nằm trong lang/{locale}.json,
     * thiếu bản dịch thì hiện nguyên câu tiếng Anh. Tham số dùng {0}, {1} như string.Format của C#.
     */
    function localize(string $key, mixed ...$arguments): string
    {
        $text = __($key);

        foreach (array_values($arguments) as $argumentIndex => $argument) {
            $text = str_replace('{'.$argumentIndex.'}', (string) $argument, $text);
        }

        return $text;
    }
}

if (! function_exists('mes_layout')) {
    /**
     * = _ViewStart.cshtml: mở trong dialog (mes-ui.js gửi header X-Mes-Dialog) thì chỉ render nội dung,
     * còn lại dùng app shell đầy đủ. View viết: @extends(mes_layout())
     */
    function mes_layout(): string
    {
        return request()->isDialogRequest() ? 'layouts.dialog' : 'layouts.app';
    }
}

if (! function_exists('mes_date')) {
    /** Định dạng ngày cố định dd/MM/yyyy HH:mm cho nhà máy VN (Localization.md). */
    function mes_date(?DateTimeInterface $dateTime, string $format = 'd/m/Y H:i'): string
    {
        return $dateTime === null ? '-' : $dateTime->format($format);
    }
}
