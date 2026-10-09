{{--
    = Views/Shared/_Layout.cshtml. App shell: sidebar trái (menu của Module đang mở) + topbar (tab Module) + nội dung.
    Menu dựng hoàn toàn phía server theo quyền của user. Biến $branding, $userSessionState, $pageNavigationContext
    do View Composer trong AppServiceProvider truyền vào (= @inject bên Razor).
--}}
@php
    $nameParts = preg_split('/\s+/', trim($userSessionState?->fullName ?? '?'), -1, PREG_SPLIT_NO_EMPTY);
    $userInitials = mb_strtoupper(implode('', array_map(fn ($namePart) => mb_substr($namePart, 0, 1), array_slice($nameParts, -2))));
    $pageTitle = $title ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="mes-antiforgery-token" content="{{ csrf_token() }}" />
    <title>{{ $pageTitle ? $pageTitle.' - ' : '' }}{{ $branding->applicationName }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('lib/bootstrap/dist/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('lib/fontawesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('lib/datatables/css/dataTables.bootstrap5.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/mes-theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/mes-layout.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/mes-components.css') }}" />
    <style>:root { --mes-brand: {{ $branding->primaryColor }}; }</style>
    @stack('styles')
</head>
<body>
    <div class="mes-shell" id="mesShell">
        <script>
            // Áp trạng thái thu gọn trước khi trình duyệt vẽ sidebar để không bị nháy.
            try { if (localStorage.getItem("mes.sidebar.collapsed") === "1") document.getElementById("mesShell").classList.add("mes-collapsed"); } catch (error) { }
        </script>

        <!-- ================= Sidebar ================= -->
        <aside class="mes-sidebar">
            <div class="mes-brand">
                <a class="mes-brand-link" href="{{ route('home') }}">
                    @if ($branding->logoUrl !== '')
                        <img class="mes-brand-logo" src="{{ $branding->logoUrl }}" alt="{{ $branding->applicationName }}" />
                    @else
                        <span class="mes-brand-text">{{ $branding->applicationName }} @if ($branding->applicationNameHighlight !== '')<em>{{ $branding->applicationNameHighlight }}</em>@endif</span>
                    @endif
                </a>
                <button type="button" class="mes-icon-button mes-collapse-button" id="mesSidebarCollapseButton" title="{{ localize('Collapse menu') }}">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
            </div>

            <nav class="mes-sidebar-scroll">
                @include('partials.sidebar-menu')
            </nav>

            <div class="mes-user">
                <span class="mes-avatar">{{ $userInitials }}</span>
                <a class="mes-user-meta" href="{{ route('account.profile') }}" title="{{ localize('My profile') }}" data-mes-dialog>
                    <span class="mes-user-name">{{ $userSessionState?->fullName }}</span>
                    <span class="mes-user-role">{{ $userSessionState && $userSessionState->roleNames !== [] ? implode(', ', $userSessionState->roleNames) : localize('No role assigned') }}</span>
                </a>
                <form method="post" action="{{ route('account.logout') }}" data-mes-confirm="{{ localize('Do you want to sign out?') }}">
                    @csrf
                    <button type="submit" class="mes-icon-button mes-logout-button" title="{{ localize('Sign out') }}">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </aside>

        <div class="mes-backdrop" id="mesBackdrop"></div>

        <!-- ================= Content ================= -->
        <div class="mes-content">
            <header class="mes-topbar">
                <button type="button" class="mes-icon-button mes-drawer-button" id="mesDrawerButton" title="{{ localize('Menu') }}">
                    <i class="fa-solid fa-bars"></i>
                </button>

                @include('partials.top-module-menu')

                <div class="mes-topbar-right">
                    @include('partials.language-switcher')
                </div>
            </header>

            <main class="mes-main">
                @include('partials.page-header', ['pageTitle' => $pageTitle])
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Dialog dùng chung cho mọi page: nội dung nạp bằng Mes.dialog.open(url) / link data-mes-dialog (public/js/mes-ui.js). --}}
    <div class="modal fade mes-dialog" id="mesDialog" tabindex="-1" aria-labelledby="mesDialogTitle" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mesDialogTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ localize('Close') }}"></button>
                </div>
                <div class="modal-body" id="mesDialogBody"></div>
            </div>
        </div>
    </div>

    <script src="{{ asset('lib/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('lib/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('lib/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('lib/datatables/js/dataTables.min.js') }}"></script>
    <script src="{{ asset('lib/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    @include('partials.client-texts')
    <script src="{{ asset('js/mes-core.js') }}"></script>
    <script src="{{ asset('js/mes-ui.js') }}"></script>
    <script src="{{ asset('js/mes-treeview.js') }}"></script>
    <script src="{{ asset('js/mes-layout.js') }}"></script>
    @include('partials.notification-scripts')
    @stack('scripts')
</body>
</html>
