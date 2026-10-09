{{-- = Areas/System/Views/UserAccount/Detail.cshtml. Mở trong dialog; sau mỗi thao tác dialog tự tải lại (ReloadDialog). --}}
@extends(mes_layout())
@section('dialogSize', 'xl')

@use('App\Support\Enums\AccountStatus')
@php
    $isPendingApproval = $userAccount->AccountStatus === AccountStatus::PendingApproval;
    $isTemporarilyLocked = $userAccount->isTemporarilyLocked();
@endphp

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-light" href="{{ route('system.user-account.index') }}" data-mes-page-only><i class="fa-solid fa-arrow-left"></i> {{ localize('Back to list') }}</a>
        <a class="btn btn-light" href="{{ route('system.user-account.permissions', $userAccount->Id) }}" data-mes-dialog><i class="fa-solid fa-key"></i> {{ localize('Permission overrides') }}</a>
    </div>

    @if ($isPendingApproval)
        <div class="mes-card" style="border-color: #f1dcb6;">
            <div class="mes-card-header" style="background: var(--mes-warn-bg);">
                <i class="fa-solid fa-hourglass-half" style="color: var(--mes-warn);"></i>
                <h3 class="mes-card-title">{{ localize('This account is waiting for approval') }}</h3>
            </div>
            <div class="mes-card-body">
                @if (! $userAccount->IsEmailVerified)
                    <div class="alert alert-warning py-2">{{ localize('The user has not verified the email address yet. They cannot sign in until the email is verified.') }}</div>
                @endif
                <form method="post" action="{{ route('system.user-account.approve', $userAccount->Id) }}">
                    @csrf
                    <label class="form-label">{{ localize('Assign roles when approving') }}</label>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        @foreach ($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roleIds[]" value="{{ $role->roleId }}" id="approveRole_{{ $role->roleId }}" />
                                <label class="form-check-label" for="approveRole_{{ $role->roleId }}">{{ $role->roleName }}</label>
                            </div>
                        @endforeach
                    </div>
                    @requireAccess('FullAccess')
                        <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> {{ localize('Approve') }}</button>
                    @endrequireAccess
                </form>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="mes-card">
                <div class="mes-card-header">
                    <h3 class="mes-card-title">{{ localize('Account information') }}</h3>
                    <div class="mes-card-actions">
                        <span class="mes-badge {{ $userAccount->AccountStatus->badgeClass() }}">{{ localize($userAccount->AccountStatus->labelKey()) }}</span>
                        @if ($isTemporarilyLocked)
                            <span class="mes-badge mes-badge-danger">{{ localize('Temporarily locked until {0}', $userAccount->LockoutEndAt->format('H:i d/m')) }}</span>
                        @endif
                    </div>
                </div>
                <div class="mes-card-body">
                    <form method="post" action="{{ route('system.user-account.update', $userAccount->Id) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">{{ localize('User name') }}</label>
                                <input class="form-control" value="{{ $userAccount->UserName }}" readonly />
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">{{ localize('Email') }}</label>
                                <input class="form-control" value="{{ $userAccount->Email }}" readonly />
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="FullName">{{ localize('Full name') }}</label>
                                <input class="form-control" id="FullName" name="FullName" value="{{ $userAccount->FullName }}" maxlength="150" required />
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">{{ localize('Email verified') }}</label>
                                <input class="form-control" value="{{ $userAccount->IsEmailVerified ? localize('Yes') : localize('No') }}" readonly />
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ localize('Roles') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($roles as $role)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="RoleIds[]" value="{{ $role->roleId }}" id="role_{{ $role->roleId }}"
                                                   @checked(in_array($role->roleId, $roleIds, true)) />
                                            <label class="form-check-label" for="role_{{ $role->roleId }}">
                                                {{ $role->roleName }}
                                                @if ($role->isSystemRole)
                                                    <i class="fa-solid fa-shield-halved text-body-secondary" title="{{ localize('System role') }}"></i>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @requireAccess('FullAccess')
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ localize('Save') }}</button>
                            </div>
                        @endrequireAccess
                    </form>
                </div>
                <div class="mes-card-footer small text-body-secondary">
                    {{ localize('Created at') }}: {{ mes_date($userAccount->CreatedAt) }}
                    &nbsp;·&nbsp; {{ localize('Last sign in') }}: {{ mes_date($userAccount->LastLoginAt) }}
                    @if ($userAccount->MustChangePassword)
                        <span class="mes-badge mes-badge-warn ms-2">{{ localize('Must change password') }}</span>
                    @endif
                </div>
            </div>
        </div>

        @requireAccess('FullAccess')
            <div class="col-xl-5">
                @if (! $isCurrentUser)
                    <div class="mes-card">
                        <div class="mes-card-header">
                            <h3 class="mes-card-title">{{ localize('Account status') }}</h3>
                        </div>
                        <div class="mes-card-body d-flex flex-wrap gap-2">
                            @if (in_array($userAccount->AccountStatus, [AccountStatus::Locked, AccountStatus::Disabled], true) || $isTemporarilyLocked)
                                <form method="post" action="{{ route('system.user-account.change-status', $userAccount->Id) }}">
                                    @csrf
                                    <input type="hidden" name="newAccountStatus" value="Active" />
                                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-lock-open"></i> {{ localize('Unlock / activate') }}</button>
                                </form>
                            @endif
                            @if ($userAccount->AccountStatus !== AccountStatus::Locked)
                                <form method="post" action="{{ route('system.user-account.change-status', $userAccount->Id) }}" data-mes-confirm="{{ localize('Lock this account?') }}" data-mes-danger>
                                    @csrf
                                    <input type="hidden" name="newAccountStatus" value="Locked" />
                                    <button type="submit" class="btn btn-light"><i class="fa-solid fa-lock"></i> {{ localize('Lock') }}</button>
                                </form>
                            @endif
                            @if ($userAccount->AccountStatus !== AccountStatus::Disabled)
                                <form method="post" action="{{ route('system.user-account.change-status', $userAccount->Id) }}" data-mes-confirm="{{ localize('Disable this account?') }}" data-mes-danger>
                                    @csrf
                                    <input type="hidden" name="newAccountStatus" value="Disabled" />
                                    <button type="submit" class="btn btn-light"><i class="fa-solid fa-user-slash"></i> {{ localize('Disable') }}</button>
                                </form>
                            @endif
                            <form method="post" action="{{ route('system.user-account.delete', $userAccount->Id) }}" data-mes-confirm="{{ localize('Delete this account?') }}" data-mes-danger class="ms-auto">
                                @csrf
                                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> {{ localize('Delete') }}</button>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="mes-card">
                    <div class="mes-card-header">
                        <h3 class="mes-card-title">{{ localize('Reset password') }}</h3>
                    </div>
                    <div class="mes-card-body">
                        <form method="post" action="{{ route('system.user-account.reset-password', $userAccount->Id) }}" data-mes-confirm="{{ localize('Set this temporary password?') }}">
                            @csrf
                            <label class="form-label" for="temporaryPassword">{{ localize('Temporary password') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="temporaryPassword" name="temporaryPassword" autocomplete="off" required minlength="{{ $passwordMinimumLength }}" />
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> {{ localize('Set') }}</button>
                            </div>
                            <div class="form-text">{{ localize('The user must change this password at the next sign in.') }}</div>
                        </form>
                    </div>
                </div>
            </div>
        @endrequireAccess
    </div>
@endsection
