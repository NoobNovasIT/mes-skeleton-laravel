{{-- = Views/Shared/_SidebarMenuNode.cshtml. Partial đệ quy: Page -> link; Module/Group -> nút mở rộng + danh sách con. --}}
@php
    $isInActivePath = $pageNavigationContext->isInActivePath($node->menuId);
    $iconClass = $node->iconClass ?: 'fa-regular fa-circle';
@endphp

@if ($node->menuType === \App\Support\Enums\MenuType::Page)
    <a class="mes-nav-link {{ $isInActivePath ? 'mes-active' : '' }}" href="{{ url($node->url) }}" title="{{ $node->displayName }}">
        <i class="{{ $iconClass }} mes-nav-icon"></i>
        <span class="mes-nav-text">{{ $node->displayName }}</span>
    </a>
@else
    <div class="mes-nav-group {{ $isInActivePath ? 'mes-open' : '' }}">
        <button type="button" class="mes-nav-link" data-mes-group-toggle aria-expanded="{{ $isInActivePath ? 'true' : 'false' }}" title="{{ $node->displayName }}">
            <i class="{{ $iconClass }} mes-nav-icon"></i>
            <span class="mes-nav-text">{{ $node->displayName }}</span>
            <i class="fa-solid fa-chevron-down mes-nav-caret"></i>
        </button>
        <div class="mes-submenu">
            @foreach ($node->children as $childNode)
                @include('partials.sidebar-menu-node', ['node' => $childNode])
            @endforeach
        </div>
    </div>
@endif
