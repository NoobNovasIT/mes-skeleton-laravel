{{--
    = Areas/System/Views/Menu/_MenuTreeNode.cshtml. Một node trên TreeView quản lý menu (đệ quy).
    Module (cấp 1) = tab trên top bar -> nút trái / phải; cấp dưới -> nút lên / xuống.
    --mes-tree-depth giúp cột chip loại / MenuCode thẳng hàng giữa các cấp.
--}}
@use('App\Support\Enums\MenuType')
@php
    $isModule = $node->menuType === MenuType::Module;
    $isPage = $node->menuType === MenuType::Page;
@endphp

<li class="mes-treeview-item" data-menu-id="{{ $node->menuId }}" data-menu-type="{{ $node->menuType->name }}" style="--mes-tree-depth: {{ $depth }}">
    <div class="mes-tree-row {{ $node->isActive ? '' : 'mes-tree-inactive' }}">
        @if ($isPage)
            <span class="mes-tree-toggle-placeholder"></span>
        @else
            <button type="button" class="mes-tree-toggle" data-mes-tree-toggle aria-expanded="true" title="{{ localize('Expand / collapse') }}">
                <i class="fa-solid fa-chevron-down"></i>
            </button>
        @endif

        @if ($canWrite)
            <i class="fa-solid fa-grip-vertical mes-tree-handle" title="{{ localize('Drag to move') }}"></i>
        @endif
        <span class="mes-tree-label" title="{{ $node->displayName }}">
            <i class="{{ $node->iconClass ?? 'fa-regular fa-circle' }} mes-tree-icon"></i>
            <span class="mes-tree-name">{{ $node->displayName }}</span>
        </span>
        <span class="mes-badge mes-tree-type {{ $node->menuType->badgeClass() }}">{{ localize($node->menuType->labelKey()) }}</span>
        <span class="mes-tree-meta mes-tree-code d-none d-md-inline">{{ $node->menuCode }}</span>
        @if ($node->url)
            <span class="mes-tree-meta d-none d-lg-inline">{{ $node->url }}</span>
        @endif
        @if (! $node->isVisibleInNavigation)
            <i class="fa-solid fa-eye-slash text-body-secondary" title="{{ localize('Hidden from navigation') }}"></i>
        @endif

        <div class="mes-tree-actions">
            @if ($canWrite)
                <div class="btn-group btn-group-sm mes-tree-move-group" role="group">
                    <button type="button" class="btn btn-light" data-mes-menu-move="previous"
                            title="{{ $isModule ? localize('Move left on the top bar') : localize('Move up') }}">
                        <i class="fa-solid {{ $isModule ? 'fa-arrow-left' : 'fa-arrow-up' }}"></i>
                    </button>
                    <button type="button" class="btn btn-light" data-mes-menu-move="next"
                            title="{{ $isModule ? localize('Move right on the top bar') : localize('Move down') }}">
                        <i class="fa-solid {{ $isModule ? 'fa-arrow-right' : 'fa-arrow-down' }}"></i>
                    </button>
                </div>
            @endif

            @if ($canWrite && ! $isPage)
                <a class="btn btn-light btn-sm" href="{{ route('system.menu.create', ['parentMenuId' => $node->menuId]) }}" data-mes-dialog title="{{ localize('Add child menu') }}">
                    <i class="fa-solid fa-plus"></i>
                </a>
            @endif
            <a class="btn btn-light btn-sm" href="{{ route('system.menu.edit', $node->menuId) }}" data-mes-dialog title="{{ localize('Edit') }}">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
            @if ($canWrite)
                @if ($node->children === [])
                    <form method="post" action="{{ route('system.menu.delete', $node->menuId) }}" class="d-inline" data-mes-ajax-form
                          data-mes-confirm="{{ localize('Delete menu {0}?', $node->displayName) }}" data-mes-danger>
                        @csrf
                        <button type="submit" class="btn btn-light btn-sm text-danger" title="{{ localize('Delete') }}"><i class="fa-solid fa-trash"></i></button>
                    </form>
                @else
                    {{-- Giữ chỗ để các cột nút thẳng hàng giữa node có và không có menu con. --}}
                    <span class="mes-tree-action-placeholder"></span>
                @endif
            @endif
        </div>
    </div>

    @if (! $isPage)
        {{-- Luôn render <ul> (kể cả rỗng) để có chỗ thả menu con khi kéo-thả. --}}
        <ul class="mes-treeview-children" data-mes-menu-list>
            @foreach ($node->children as $childNode)
                @include('system.menu.menu-tree-node', ['node' => $childNode, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
