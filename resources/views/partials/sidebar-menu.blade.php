{{-- = ViewComponents/SidebarMenuViewComponent + Components/SidebarMenu/Default.cshtml: con của Module đang mở. --}}
<a class="mes-nav-link {{ request()->is('/') ? 'mes-active' : '' }}" href="{{ route('home') }}" title="{{ localize('Home') }}">
    <i class="fa-solid fa-house mes-nav-icon"></i>
    <span class="mes-nav-text">{{ localize('Home') }}</span>
</a>

@if ($pageNavigationContext->activeModule === null)
    <div class="mes-menu-empty">{{ localize('You have not been granted any menu yet.') }}</div>
@else
    <div class="mes-menu-group-label mt-2">{{ $pageNavigationContext->activeModule->displayName }}</div>
    @foreach ($pageNavigationContext->activeModule->children as $childNode)
        @include('partials.sidebar-menu-node', ['node' => $childNode])
    @endforeach
@endif
