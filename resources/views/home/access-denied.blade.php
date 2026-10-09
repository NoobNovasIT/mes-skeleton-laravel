{{-- = Views/Home/AccessDenied.cshtml --}}
@extends('layouts.app', ['title' => localize('Access denied')])

@section('content')
    <div class="mes-state">
        <div class="mes-state-icon" style="background: var(--mes-danger-bg); color: var(--mes-danger);">
            <i class="fa-solid fa-lock"></i>
        </div>
        <h2>{{ localize('You do not have permission to access this page.') }}</h2>
        <p class="text-body-secondary">{{ localize('If you need access, please contact the administrator.') }}</p>
        <a class="btn btn-primary" href="{{ route('home') }}">
            <i class="fa-solid fa-house"></i> {{ localize('Back to home') }}
        </a>
    </div>
@endsection
