{{-- = Views/Account/Profile.cshtml. Bấm tên người dùng cuối sidebar -> mở trong dialog. --}}
@extends(mes_layout(), ['title' => localize('My profile')])
@section('dialogSize', 'xl')

@section('content')
    <div class="row g-3">
        <div class="col-lg-7 col-xl-6">
            <div class="mes-card">
                <div class="mes-card-header">
                    <h3 class="mes-card-title">{{ localize('Account information') }}</h3>
                </div>
                <div class="mes-card-body">
                    <form method="post" action="{{ route('account.profile') }}" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">{{ localize('User name') }}</label>
                                <input class="form-control" value="{{ $profile['UserName'] }}" readonly />
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">{{ localize('Email') }}</label>
                                <input class="form-control" value="{{ $profile['Email'] }}" readonly />
                            </div>
                            <div class="col-sm-6">
                                <label for="FullName" class="form-label">{{ localize('Full name') }}</label>
                                <input id="FullName" name="FullName" class="form-control" value="{{ $profile['FullName'] }}" maxlength="150" />
                            </div>
                            <div class="col-sm-6">
                                <label for="PreferredLanguageCode" class="form-label">{{ localize('Language') }}</label>
                                <select id="PreferredLanguageCode" name="PreferredLanguageCode" class="form-select">
                                    @foreach ($languages as $language)
                                        <option value="{{ $language->languageCode }}" @selected($language->languageCode === $profile['PreferredLanguageCode'])>{{ $language->languageName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ localize('Save') }}</button>
                            <a class="btn btn-light" href="{{ route('account.change-password') }}" data-mes-dialog><i class="fa-solid fa-key"></i> {{ localize('Change password') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-xl-4">
            <div class="mes-card">
                <div class="mes-card-header">
                    <h3 class="mes-card-title">{{ localize('Access') }}</h3>
                </div>
                <div class="mes-card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-body-secondary fw-normal">{{ localize('Roles') }}</dt>
                        <dd class="col-7">
                            @forelse ($profile['RoleNames'] as $roleName)
                                <span class="mes-badge mes-badge-info me-1">{{ $roleName }}</span>
                            @empty
                                <span class="mes-badge mes-badge-muted">{{ localize('No role assigned') }}</span>
                            @endforelse
                        </dd>
                        <dt class="col-5 text-body-secondary fw-normal">{{ localize('Created at') }}</dt>
                        <dd class="col-7">{{ mes_date($profile['CreatedAt']) }}</dd>
                        <dt class="col-5 text-body-secondary fw-normal">{{ localize('Last sign in') }}</dt>
                        <dd class="col-7 mb-0">{{ mes_date($profile['LastLoginAt']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
