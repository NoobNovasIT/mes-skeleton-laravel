{{-- = Areas/System/Views/UserAccount/Index.cshtml. Lọc + phân trang chỉ tải lại vùng "user-account-list". --}}
@extends('layouts.app')

@use('App\Support\Enums\AccountStatus')
@php
    $buildPageUrl = fn (int $pageNumber) => route('system.user-account.index', array_filter([
        'keyword' => $keyword,
        'accountStatus' => $accountStatus?->name,
        'roleId' => $roleId,
        'pageNumber' => $pageNumber,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

@section('content')
    <div class="mes-card">
        <div class="mes-card-body">
            <form method="get" action="{{ route('system.user-account.index') }}" class="mes-filter-bar" data-mes-region-form="user-account-list">
                <div class="mes-filter-item flex-grow-1">
                    <label class="form-label" for="keyword">{{ localize('Keyword') }}</label>
                    <input class="form-control" id="keyword" name="keyword" value="{{ $keyword }}" placeholder="{{ localize('User name, email or full name') }}" />
                </div>
                <div class="mes-filter-item">
                    <label class="form-label" for="accountStatus">{{ localize('Status') }}</label>
                    <select class="form-select" id="accountStatus" name="accountStatus">
                        <option value="">{{ localize('All') }}</option>
                        @foreach (AccountStatus::cases() as $accountStatusOption)
                            <option value="{{ $accountStatusOption->name }}" @selected($accountStatus === $accountStatusOption)>{{ localize($accountStatusOption->labelKey()) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mes-filter-item">
                    <label class="form-label" for="roleId">{{ localize('Role') }}</label>
                    <select class="form-select" id="roleId" name="roleId">
                        <option value="">{{ localize('All') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->roleId }}" @selected($roleId === $role->roleId)>{{ $role->roleName }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> {{ localize('Search') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mes-card" data-mes-refresh-region="user-account-list">
        <div class="mes-card-body mes-card-body-flush table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ localize('User name') }}</th>
                        <th>{{ localize('Full name') }}</th>
                        <th>{{ localize('Email') }}</th>
                        <th>{{ localize('Roles') }}</th>
                        <th>{{ localize('Status') }}</th>
                        <th>{{ localize('Last sign in') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($searchResult->items as $userAccount)
                        <tr>
                            <td class="fw-medium">{{ $userAccount->UserName }}</td>
                            <td>{{ $userAccount->FullName }}</td>
                            <td>
                                {{ $userAccount->Email }}
                                @if (! $userAccount->IsEmailVerified)
                                    <i class="fa-solid fa-circle-exclamation text-warning ms-1" title="{{ localize('Email not verified') }}"></i>
                                @endif
                            </td>
                            <td>
                                @foreach ($userAccount->roleNames() as $roleName)
                                    <span class="mes-badge mes-badge-info me-1">{{ $roleName }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="mes-badge {{ $userAccount->AccountStatus->badgeClass() }}">{{ localize($userAccount->AccountStatus->labelKey()) }}</span>
                                @if ($userAccount->isTemporarilyLocked())
                                    <span class="mes-badge mes-badge-danger">{{ localize('Temporarily locked') }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ mes_date($userAccount->LastLoginAt) }}</td>
                            <td class="text-end">
                                <a class="btn btn-light btn-sm" href="{{ route('system.user-account.detail', $userAccount->Id) }}" data-mes-dialog>
                                    <i class="fa-solid fa-pen-to-square"></i> {{ localize('Detail') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ localize('No data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mes-card-footer">
            @include('partials.pager', ['pagedResult' => $searchResult, 'regionName' => 'user-account-list'])
        </div>
    </div>
@endsection
