<?php

/*
 | Cấu hình riêng của MES (= các section Localization, Authentication trong appsettings.json).
 | Đọc trong code bằng config('mes.default_language_code'). Không gọi env() ngoài thư mục config,
 | vì sau "php artisan config:cache" env() trả null.
 */
return [

    'default_language_code' => env('MES_DEFAULT_LANGUAGE', 'en'),

    'supported_language_codes' => array_map('trim', explode(',', env('MES_SUPPORTED_LANGUAGES', 'en,vi'))),

    // Cookie lưu ngôn ngữ đang chọn (= .AspNetCore.Culture), sống 1 năm.
    'language_cookie_name' => 'mes_language',

];
