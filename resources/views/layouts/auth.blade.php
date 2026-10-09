{{-- = Views/Shared/_AuthLayout.cshtml: trang chưa đăng nhập (Login), video nền + card kính mờ. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ isset($title) ? $title.' - ' : '' }}{{ $branding->applicationName }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('lib/bootstrap/dist/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('lib/fontawesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/mes-theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/mes-components.css') }}" />
    <style>:root { --mes-brand: {{ $branding->primaryColor }}; }</style>
</head>
<body>
    @if ($branding->loginBackgroundVideoUrls !== [])
        {{-- 2 thẻ <video> chồng nhau, public/js/mes-auth-background.js luân phiên và fade giữa hai video. --}}
        <div class="mes-auth-background" id="mesAuthBackground" aria-hidden="true"
             data-video-urls="{{ json_encode(array_map(fn ($videoUrl) => asset(ltrim($videoUrl, '/')), $branding->loginBackgroundVideoUrls)) }}">
            <video class="mes-auth-video" muted playsinline preload="auto"></video>
            <video class="mes-auth-video" muted playsinline preload="auto"></video>
            <div class="mes-auth-overlay"></div>
        </div>
    @endif

    <div class="mes-auth-page {{ $branding->loginBackgroundVideoUrls !== [] ? 'mes-auth-page-video' : '' }}">
        <div class="w-100" style="max-width: 420px;">
            <div class="mes-auth-card">
                <div class="mes-auth-brand">
                    @if ($branding->logoUrl !== '')
                        <img class="mes-auth-brand-logo" src="{{ $branding->logoUrl }}" alt="{{ $branding->applicationName }}" />
                    @endif
                    <span class="mes-auth-brand-text">
                        {{ $branding->applicationName }}
                        @if ($branding->applicationNameHighlight !== '')
                            <em>{{ $branding->applicationNameHighlight }}</em>
                        @endif
                    </span>
                </div>

                @yield('content')

                <div class="mes-auth-language">
                    @include('partials.language-switcher')
                </div>
            </div>

            @if ($branding->companyName !== '')
                <div class="mes-auth-company">&copy; {{ now()->year }} {{ $branding->companyName }}</div>
            @endif
        </div>
    </div>

    <script src="{{ asset('lib/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('lib/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    @include('partials.client-texts')
    <script src="{{ asset('js/mes-core.js') }}"></script>
    <script src="{{ asset('js/mes-auth-background.js') }}"></script>
    @include('partials.notification-scripts')
</body>
</html>
