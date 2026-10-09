{{-- = TopModuleMenuViewComponent: tab Module trên topbar, trỏ tới Page đầu tiên user được phép vào. --}}
<nav class="mes-module-tabs">
    @foreach ($pageNavigationContext->navigation->modules as $module)
        @php
            $moduleUrl = $module->findFirstPageUrl();
            $isActive = $pageNavigationContext->activePath !== [] && $pageNavigationContext->activeModule?->menuId === $module->menuId;
        @endphp
        <a class="mes-module-tab {{ $isActive ? 'mes-active' : '' }}" href="{{ $moduleUrl === null ? '#' : url($moduleUrl) }}">
            @if ($module->iconClass)
                <i class="{{ $module->iconClass }} mes-module-tab-icon"></i>
            @endif
            <span>{{ $module->displayName }}</span>
        </a>
    @endforeach
</nav>
