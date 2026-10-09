{{-- = Views/Account/Login.cshtml. Đăng ký / quên mật khẩu chưa port (cần gửi email). --}}
@extends('layouts.auth', ['title' => localize('Sign in')])

@section('content')
    <h1 class="mes-auth-title">{{ localize('Sign in') }}</h1>
    <p class="mes-auth-subtitle">{{ localize('Use your user name or email address.') }}</p>

    <form method="post" action="{{ route('account.login') }}" novalidate>
        @csrf
        <input type="hidden" name="ReturnUrl" value="{{ $loginViewModel->ReturnUrl }}" />
        @include('partials.validation-summary')

        <div class="mb-3">
            <label for="LoginName" class="form-label">{{ localize('User name or email') }}</label>
            <input id="LoginName" name="LoginName" value="{{ $loginViewModel->LoginName }}" class="form-control" autocomplete="username" autofocus />
            @include('partials.field-error', ['field' => 'LoginName'])
        </div>

        <div class="mb-3">
            <label for="Password" class="form-label">{{ localize('Password') }}</label>
            <input id="Password" name="Password" type="password" class="form-control" autocomplete="current-password" />
            @include('partials.field-error', ['field' => 'Password'])
        </div>

        <button type="submit" class="btn btn-primary w-100 justify-content-center py-2">
            <i class="fa-solid fa-right-to-bracket"></i> {{ localize('Sign in') }}
        </button>
    </form>
@endsection
