{{-- = Areas/System/Views/Menu/Index.cshtml. TreeView: kéo-thả / mũi tên đổi thứ tự, bấm Save order mới lưu. --}}
@extends('layouts.app')

@php($canWrite = \App\Http\Permissions\PagePermission::current()->canWrite())

@section('content')
    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info"></i>
        {{ localize('Module = tab on the top bar. Group = folder in the sidebar. Page = a screen with a URL and permissions. Use the arrow buttons or drag the handle to change the order, then press Save order. Nothing is applied until you save.') }}
    </div>

    <div class="mes-card" data-mes-refresh-region="menu-tree" id="menuTreeCard">
        <div class="mes-card-header">
            <h3 class="mes-card-title">{{ localize('Menu tree') }}</h3>
            <span class="mes-badge mes-badge-warn" id="menuTreeUnsavedBadge" hidden>
                <i class="fa-solid fa-circle-exclamation"></i> {{ localize('Unsaved changes') }}
            </span>
            <div class="mes-card-actions">
                <button type="button" class="btn btn-light" data-mes-tree-expand-all><i class="fa-solid fa-angles-down"></i> {{ localize('Expand all') }}</button>
                <button type="button" class="btn btn-light" data-mes-tree-collapse-all><i class="fa-solid fa-angles-up"></i> {{ localize('Collapse all') }}</button>
                @if ($canWrite)
                    <button type="button" class="btn btn-light" id="discardMenuPositionsButton" hidden>
                        <i class="fa-solid fa-rotate-left"></i> {{ localize('Discard changes') }}
                    </button>
                    <button type="button" class="btn btn-light" id="saveMenuPositionsButton" data-save-url="{{ route('system.menu.save-positions') }}" disabled>
                        <i class="fa-solid fa-floppy-disk"></i> {{ localize('Save order') }}
                    </button>
                    <a class="btn btn-primary" href="{{ route('system.menu.create') }}" data-mes-dialog><i class="fa-solid fa-plus"></i> {{ localize('Create module') }}</a>
                @endif
            </div>
        </div>
        <div class="mes-card-body">
            <ul class="mes-treeview" data-mes-menu-list data-mes-menu-root
                data-mes-tree-root data-mes-tree-storage-key="mes.menuTree.collapsedMenuIds">
                @foreach ($menuTree as $node)
                    @include('system.menu.menu-tree-node', ['node' => $node, 'depth' => 0])
                @endforeach
            </ul>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('lib/sortablejs/Sortable.min.js') }}"></script>
    <script src="{{ asset('js/pages/system-menu.js') }}"></script>
@endpush
