{{--
    = Views/Shared/_LanguageSwitcher.cshtml. Dùng chung cho topbar và trang đăng nhập.
    <details> thay <select> vì <option> không hiện được ảnh cờ. Cờ: public/images/flags/{languageCode}.svg,
    thiếu file thì hiện icon quả địa cầu. $languages do View Composer truyền vào.
--}}
@php
    $currentLanguageCode = app()->getLocale();
    $currentLanguage = collect($languages)->firstWhere('languageCode', $currentLanguageCode) ?? ($languages[0] ?? null);
    $currentUrl = request()->getRequestUri();
    $buildFlagUrl = fn (string $languageCode) => file_exists(public_path("images/flags/{$languageCode}.svg")) ? asset("images/flags/{$languageCode}.svg") : null;
@endphp

@if ($currentLanguage !== null)
    <form method="post" action="{{ route('home.set-language') }}">
        @csrf
        <input type="hidden" name="returnUrl" value="{{ $currentUrl }}" />

        <details class="mes-language-switcher" data-mes-language-switcher>
            <summary class="mes-language-current" title="{{ localize('Language') }}" aria-label="{{ localize('Language') }}">
                @if ($currentFlagUrl = $buildFlagUrl($currentLanguage->languageCode))
                    <img class="mes-language-flag" src="{{ $currentFlagUrl }}" alt="" />
                @else
                    <i class="fa-solid fa-globe mes-language-flag-fallback"></i>
                @endif
                <span class="mes-language-name">{{ $currentLanguage->languageName }}</span>
                <i class="fa-solid fa-chevron-down mes-language-caret"></i>
            </summary>

            <div class="mes-language-menu">
                @foreach ($languages as $language)
                    @php
                        $flagUrl = $buildFlagUrl($language->languageCode);
                        $isCurrentLanguage = $language->languageCode === $currentLanguage->languageCode;
                    @endphp
                    <button type="submit" name="languageCode" value="{{ $language->languageCode }}"
                            class="mes-language-option {{ $isCurrentLanguage ? 'mes-active' : '' }}">
                        @if ($flagUrl)
                            <img class="mes-language-flag" src="{{ $flagUrl }}" alt="" />
                        @else
                            <i class="fa-solid fa-globe mes-language-flag-fallback"></i>
                        @endif
                        <span class="mes-language-name">{{ $language->languageName }}</span>
                        @if ($isCurrentLanguage)
                            <i class="fa-solid fa-check mes-language-check"></i>
                        @endif
                    </button>
                @endforeach
            </div>
        </details>
    </form>
@endif
