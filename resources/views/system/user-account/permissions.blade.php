{{-- = Areas/System/Views/UserAccount/Permissions.cshtml: ghi đè quyền cho riêng user (mở trong cùng dialog với Detail). --}}
@extends(mes_layout())
@section('dialogSize', 'xl')

@php($isReadOnly = ! \App\Http\Permissions\PagePermission::current()->canWrite())

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-light" href="{{ route('system.user-account.detail', $userAccount->Id) }}" data-mes-dialog><i class="fa-solid fa-arrow-left"></i> {{ localize('Back') }}</a>
    </div>

    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info"></i>
        {{ localize('By default a user receives the highest access level of all assigned roles. Choose a level here only when this user needs an exception; an override replaces the role level, even if it is lower.') }}
    </div>

    <form method="post" action="{{ route('system.user-account.permissions', $userAccount->Id) }}">
        @csrf
        <div class="mes-card">
            <div class="mes-card-header">
                <h3 class="mes-card-title">{{ $userAccount->FullName }}</h3>
                <div class="mes-card-actions">
                    @foreach ($userAccount->roleNames() as $roleName)
                        <span class="mes-badge mes-badge-info">{{ $roleName }}</span>
                    @endforeach
                    <button type="button" class="btn btn-light btn-sm" data-mes-tree-expand-all><i class="fa-solid fa-angles-down"></i> {{ localize('Expand all') }}</button>
                    <button type="button" class="btn btn-light btn-sm" data-mes-tree-collapse-all><i class="fa-solid fa-angles-up"></i> {{ localize('Collapse all') }}</button>
                </div>
            </div>
            <div class="mes-card-body">
                <ul class="mes-treeview" data-mes-tree-root data-mes-tree-storage-key="mes.permissionTree.collapsedMenuIds">
                    @foreach ($permissionTree as $node)
                        @include('system.shared.permission-tree-node', ['node' => $node, 'isUserOverrideMode' => true, 'isReadOnly' => $isReadOnly, 'depth' => 0])
                    @endforeach
                </ul>
            </div>
            <div class="mes-card-footer">
                @requireAccess('FullAccess')
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ localize('Save') }}</button>
                @endrequireAccess
                <a class="btn btn-light" href="{{ route('system.user-account.index') }}" data-mes-dialog-close data-mes-dialog-only>{{ localize('Close') }}</a>
            </div>
        </div>
    </form>
@endsection
