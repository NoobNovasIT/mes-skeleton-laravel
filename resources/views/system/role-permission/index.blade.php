{{--
    = Areas/System/Views/RolePermission/Index.cshtml.
    Trang đầy đủ: danh sách Role + cây quyền (vùng "role-permission" tải lại khi chọn Role). Dialog: chỉ cây quyền.
--}}
@extends(mes_layout())
@section('dialogSize', 'xl')

@php
    $isDialogRequest = request()->isDialogRequest();
    $isReadOnly = ! \App\Http\Permissions\PagePermission::current()->canWrite() || $selectedRole?->isSystemRole === true;
@endphp

@section('content')
    <div class="row g-3" data-mes-refresh-region="role-permission">
        @if (! $isDialogRequest)
            <div class="col-lg-3">
                <div class="mes-card">
                    <div class="mes-card-header">
                        <h3 class="mes-card-title">{{ localize('Roles') }}</h3>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach ($roles as $role)
                            @php($isSelectedRole = $role->roleId === $selectedRole?->roleId)
                            <a class="list-group-item list-group-item-action d-flex align-items-center {{ $isSelectedRole ? 'active' : '' }}"
                               style="{{ $isSelectedRole ? 'background: var(--mes-brand); border-color: var(--mes-brand);' : '' }}"
                               href="{{ route('system.role-permission.index', ['roleId' => $role->roleId]) }}" data-mes-region-link="role-permission">
                                <span class="flex-grow-1">{{ $role->roleName }}</span>
                                @if ($role->isSystemRole)
                                    <i class="fa-solid fa-shield-halved small opacity-75"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ $isDialogRequest ? 'col-12' : 'col-lg-9' }}">
            @if ($selectedRole === null)
                <div class="alert alert-info">{{ localize('No role yet. Create a role first.') }}</div>
            @else
                @if ($selectedRole->isSystemRole)
                    <div class="alert alert-info">
                        <i class="fa-solid fa-shield-halved"></i>
                        {{ localize('System roles always have full access to every page and cannot be edited.') }}
                    </div>
                @endif

                <form method="post" action="{{ route('system.role-permission.save', $selectedRole->roleId) }}" data-mes-ajax-form data-mes-permission-form>
                    @csrf
                    <div class="mes-card">
                        <div class="mes-card-header">
                            <h3 class="mes-card-title">{{ $selectedRole->roleName }} <code class="ms-1">{{ $selectedRole->roleCode }}</code></h3>
                            <div class="mes-card-actions">
                                <button type="button" class="btn btn-light btn-sm" data-mes-tree-expand-all><i class="fa-solid fa-angles-down"></i> {{ localize('Expand all') }}</button>
                                <button type="button" class="btn btn-light btn-sm" data-mes-tree-collapse-all><i class="fa-solid fa-angles-up"></i> {{ localize('Collapse all') }}</button>
                            </div>
                        </div>
                        <div class="mes-card-body">
                            <div class="mes-tree-legend">
                                <span class="mes-badge mes-badge-muted">{{ localize('No access') }}</span> {{ localize('menu hidden') }}
                                <span class="mes-badge mes-badge-info ms-2">{{ localize('Read only') }}</span> {{ localize('view only') }}
                                <span class="mes-badge mes-badge-ok ms-2">{{ localize('Full access') }}</span> {{ localize('view, add, edit, delete') }}
                            </div>
                            <ul class="mes-treeview" data-mes-tree-root data-mes-tree-storage-key="mes.permissionTree.collapsedMenuIds">
                                @foreach ($permissionTree as $node)
                                    @include('system.shared.permission-tree-node', ['node' => $node, 'isUserOverrideMode' => false, 'isReadOnly' => $isReadOnly, 'depth' => 0])
                                @endforeach
                            </ul>
                        </div>
                        <div class="mes-card-footer">
                            @if (! $isReadOnly)
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ localize('Save') }}</button>
                            @endif
                            <a class="btn btn-light" href="{{ route('system.role.index') }}" data-mes-dialog-close data-mes-dialog-only>{{ localize('Close') }}</a>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/system-role-permission.js') }}"></script>
@endpush
