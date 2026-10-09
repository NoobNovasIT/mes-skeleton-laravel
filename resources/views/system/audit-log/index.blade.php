{{-- = Areas/System/Views/AuditLog/Index.cshtml. Truy vấn qua SP usp_System_AuditLog_Search, 50 dòng / trang. --}}
@extends('layouts.app')

@use('App\Support\Constants\AuditActionTypes')
@php
    $buildPageUrl = fn (int $pageNumber) => route('system.audit-log.index', array_filter([
        'fromDate' => $fromDate->format('Y-m-d'),
        'toDate' => $toDate->format('Y-m-d'),
        'actionType' => $actionType,
        'keyword' => $keyword,
        'pageNumber' => $pageNumber,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

@section('content')
    <div class="mes-card">
        <div class="mes-card-body">
            <form method="get" action="{{ route('system.audit-log.index') }}" class="mes-filter-bar" data-mes-region-form="audit-log-list">
                <div class="mes-filter-item">
                    <label class="form-label" for="fromDate">{{ localize('From date') }}</label>
                    <input type="date" class="form-control" id="fromDate" name="fromDate" value="{{ $fromDate->format('Y-m-d') }}" />
                </div>
                <div class="mes-filter-item">
                    <label class="form-label" for="toDate">{{ localize('To date') }}</label>
                    <input type="date" class="form-control" id="toDate" name="toDate" value="{{ $toDate->format('Y-m-d') }}" />
                </div>
                <div class="mes-filter-item">
                    <label class="form-label" for="actionType">{{ localize('Action') }}</label>
                    <select class="form-select" id="actionType" name="actionType">
                        <option value="">{{ localize('All') }}</option>
                        @foreach (AuditActionTypes::All as $actionTypeOption)
                            <option value="{{ $actionTypeOption }}" @selected($actionType === $actionTypeOption)>{{ $actionTypeOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mes-filter-item flex-grow-1">
                    <label class="form-label" for="keyword">{{ localize('Keyword') }}</label>
                    <input class="form-control" id="keyword" name="keyword" value="{{ $keyword }}" placeholder="{{ localize('User, entity, description') }}" />
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> {{ localize('Search') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mes-card" data-mes-refresh-region="audit-log-list">
        <div class="mes-card-body mes-card-body-flush table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ localize('Time') }}</th>
                        <th>{{ localize('User') }}</th>
                        <th>{{ localize('Action') }}</th>
                        <th>{{ localize('Entity') }}</th>
                        <th>{{ localize('Description') }}</th>
                        <th>{{ localize('Old value') }}</th>
                        <th>{{ localize('New value') }}</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($searchResult->items as $auditLog)
                        <tr>
                            <td class="text-nowrap">{{ mes_date($auditLog->createdAt, 'd/m/Y H:i:s') }}</td>
                            <td>{{ $auditLog->userName }}</td>
                            <td><span class="mes-badge {{ AuditActionTypes::badgeClass($auditLog->actionType) }}">{{ $auditLog->actionType }}</span></td>
                            <td class="text-nowrap">
                                {{ $auditLog->entityName }}
                                @if ($auditLog->entityId)
                                    <span class="mes-tree-meta">#{{ $auditLog->entityId }}</span>
                                @endif
                            </td>
                            <td>{{ $auditLog->description }}</td>
                            <td>@if ($auditLog->oldValueJson !== null)<pre class="mes-json">{{ $auditLog->oldValueJson }}</pre>@endif</td>
                            <td>@if ($auditLog->newValueJson !== null)<pre class="mes-json">{{ $auditLog->newValueJson }}</pre>@endif</td>
                            <td class="mes-tree-meta">{{ $auditLog->ipAddress }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-4">{{ localize('No data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mes-card-footer">
            @include('partials.pager', ['pagedResult' => $searchResult, 'regionName' => 'audit-log-list'])
        </div>
    </div>
@endsection
