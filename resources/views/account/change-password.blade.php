{{-- = Views/Account/ChangePassword.cshtml. Mở trong dialog (từ My profile) hoặc trang đầy đủ (bị ép đổi mật khẩu). --}}
@extends(mes_layout(), ['title' => localize('Change password')])
@section('dialogSize', 'md')

@section('content')
    <div class="row">
        <div class="{{ request()->isDialogRequest() ? 'col-12' : 'col-lg-6 col-xl-5' }}">
            @if ($changePasswordViewModel->IsPasswordChangeRequired)
                <div class="alert alert-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    {{ localize('You must change your password before continuing.') }}
                </div>
            @endif

            <div class="mes-card">
                <div class="mes-card-body">
                    <form method="post" action="{{ route('account.change-password') }}" novalidate>
                        @csrf
                        @include('partials.validation-summary')

                        <div class="mb-3">
                            <label for="CurrentPassword" class="form-label">{{ localize('Current password') }}</label>
                            <input id="CurrentPassword" name="CurrentPassword" type="password" class="form-control" autocomplete="current-password" autofocus />
                            @include('partials.field-error', ['field' => 'CurrentPassword'])
                        </div>
                        <div class="mb-3">
                            <label for="NewPassword" class="form-label">{{ localize('New password') }}</label>
                            <input id="NewPassword" name="NewPassword" type="password" class="form-control" autocomplete="new-password" />
                            <div class="form-text">{{ localize('At least {0} characters, including letters and digits.', $changePasswordViewModel->PasswordMinimumLength) }}</div>
                            @include('partials.field-error', ['field' => 'NewPassword'])
                        </div>
                        <div class="mb-3">
                            <label for="ConfirmNewPassword" class="form-label">{{ localize('Confirm new password') }}</label>
                            <input id="ConfirmNewPassword" name="ConfirmNewPassword" type="password" class="form-control" autocomplete="new-password" />
                            @include('partials.field-error', ['field' => 'ConfirmNewPassword'])
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> {{ localize('Save') }}
                        </button>
                        <a class="btn btn-light" href="{{ route('home') }}" data-mes-dialog-close data-mes-dialog-only>{{ localize('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
