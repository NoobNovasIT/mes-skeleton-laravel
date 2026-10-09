{{-- = Views/Home/Index.cshtml: lời chào + lối tắt tới mọi Page user được phép vào. --}}
@extends('layouts.app', ['title' => localize('Home')])

@section('content')
    <div class="mes-card">
        <div class="mes-card-body">
            <h2 class="mb-1">{{ localize('Welcome, {0}', $fullName) }}</h2>
            <p class="text-body-secondary mb-0">{{ now()->locale(app()->getLocale())->isoFormat('dddd, DD/MM/YYYY') }}</p>
        </div>
    </div>

    @if ($isPendingApproval)
        <div class="alert alert-warning d-flex gap-3 align-items-start">
            <i class="fa-solid fa-hourglass-half mt-1"></i>
            <div>
                <strong>{{ localize('Your account is waiting for approval.') }}</strong><br />
                {{ localize('An administrator will review your registration and grant access. You will receive an email when your account is approved.') }}
            </div>
        </div>
    @endif

    @if ($pendingApprovalUserCount > 0)
        <div class="mes-stat-grid">
            <a class="mes-stat-tile mes-stat-orange" href="{{ route('system.user-account.index', ['accountStatus' => 'PendingApproval']) }}">
                <span class="mes-stat-value">{{ $pendingApprovalUserCount }}</span>
                <span class="mes-stat-label">{{ localize('Accounts waiting for approval') }}</span>
            </a>
        </div>
    @endif

    @foreach ($modules as $module)
        <div class="mes-card">
            <div class="mes-card-header">
                <i class="{{ $module->iconClass ?: 'fa-solid fa-folder' }} text-body-secondary"></i>
                <h3 class="mes-card-title">{{ $module->displayName }}</h3>
            </div>
            <div class="mes-card-body">
                <div class="mes-quick-link-grid">
                    @foreach ($module->listPages() as $pageNode)
                        <a class="mes-quick-link" href="{{ url($pageNode->url) }}">
                            <i class="{{ $pageNode->iconClass ?: 'fa-regular fa-circle' }}"></i>
                            <span>{{ $pageNode->displayName }}</span>
                            @if ($pageNode->accessLevel === \App\Support\Enums\AccessLevel::ReadOnly)
                                <span class="mes-badge mes-badge-info ms-auto">{{ localize('Read only') }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
@endsection
