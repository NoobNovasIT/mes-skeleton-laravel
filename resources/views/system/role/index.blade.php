{{-- = Areas/System/Views/Role/Index.cshtml. $roles: list<RoleListItemDto> --}}
@extends('layouts.app')

@section('content')
    <div data-mes-refresh-region="role-list">
        <div class="mes-card">
            <div class="mes-card-header">
                <h3 class="mes-card-title">{{ localize('Roles') }}</h3>
                <div class="mes-card-actions">
                    <a class="btn btn-light" href="{{ route('system.role-permission.index') }}"><i class="fa-solid fa-key"></i> {{ localize('Role permissions') }}</a>
                    @requireAccess('FullAccess')
                        <a class="btn btn-primary" href="{{ route('system.role.create') }}" data-mes-dialog><i class="fa-solid fa-plus"></i> {{ localize('Create') }}</a>
                    @endrequireAccess
                </div>
            </div>
            <div class="mes-card-body">
                <table class="table table-hover" data-mes-datatable>
                    <thead>
                        <tr>
                            <th>{{ localize('Role code') }}</th>
                            <th>{{ localize('Role name') }}</th>
                            <th>{{ localize('Description') }}</th>
                            <th class="text-end">{{ localize('Users') }}</th>
                            <th data-orderable="false"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td class="fw-medium">
                                    <code>{{ $role->roleCode }}</code>
                                    @if ($role->isSystemRole)
                                        <span class="mes-badge mes-badge-muted ms-1">{{ localize('System') }}</span>
                                    @endif
                                </td>
                                <td>{{ $role->roleName }}</td>
                                <td class="text-body-secondary">{{ $role->description }}</td>
                                <td class="text-end">
                                    <a href="{{ route('system.user-account.index', ['roleId' => $role->roleId]) }}">{{ $role->userCount }}</a>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-light btn-sm" href="{{ route('system.role-permission.index', ['roleId' => $role->roleId]) }}"
                                       data-mes-dialog title="{{ localize('Permissions') }}">
                                        <i class="fa-solid fa-key"></i>
                                    </a>
                                    <a class="btn btn-light btn-sm" href="{{ route('system.role.edit', $role->roleId) }}" data-mes-dialog title="{{ localize('Edit') }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @if (! $role->isSystemRole)
                                        @requireAccess('FullAccess')
                                            <form method="post" action="{{ route('system.role.delete', $role->roleId) }}" class="d-inline" data-mes-ajax-form
                                                  data-mes-confirm="{{ localize('Delete role {0}? Users will lose this role.', $role->roleName) }}" data-mes-danger>
                                                @csrf
                                                <button type="submit" class="btn btn-light btn-sm text-danger" title="{{ localize('Delete') }}"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endrequireAccess
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

{{-- Nút chìa khóa mở cây phân quyền trong dialog; dialog bỏ qua @push('scripts') nên nút "Set all" phải nạp ở trang này. --}}
@push('scripts')
    <script src="{{ asset('js/pages/system-role-permission.js') }}"></script>
@endpush
