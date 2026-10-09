<?php

namespace App\Services\Languages;

final class LanguageDto
{
    public function __construct(
        public readonly string $languageCode,
        public readonly string $languageName,
    ) {
    }
}
