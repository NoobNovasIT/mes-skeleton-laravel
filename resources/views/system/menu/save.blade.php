{{-- = Areas/System/Views/Menu/Save.cshtml - dùng chung Create + Edit (mở trong dialog). --}}
@extends(mes_layout())
@section('dialogSize', 'lg')

@use('App\Support\Enums\MenuType')
@php
    $isReadOnly = ! \App\Http\Permissions\PagePermission::current()->canWrite();
    $formAction = $menuSaveViewModel->isCreateMode() ? route('system.menu.create') : route('system.menu.edit', $menuSaveViewModel->MenuId);
@endphp

@section('content')
    <form method="post" action="{{ $formAction }}" novalidate>
        @csrf
        <div class="row g-3">
            <div class="col-xl-7">
                <div class="mes-card">
                    <div class="mes-card-header">
                        <h3 class="mes-card-title">{{ localize('Menu information') }}</h3>
                    </div>
                    <div class="mes-card-body">
                        @include('partials.validation-summary')

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="MenuType" class="form-label">{{ localize('Menu type') }}</label>
                                {{-- value là TÊN enum (Module / Group / Page): system-menu.js ẩn / hiện field theo tên này. --}}
                                <select id="MenuType" name="MenuType" class="form-select" data-mes-menu-type-select @disabled($isReadOnly)>
                                    @foreach (MenuType::cases() as $menuType)
                                        <option value="{{ $menuType->name }}" @selected($menuType === $menuSaveViewModel->MenuType)>{{ localize($menuType->labelKey()) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6" data-mes-menu-parent-field>
                                <label for="ParentMenuId" class="form-label">{{ localize('Parent menu') }}</label>
                                <select id="ParentMenuId" name="ParentMenuId" class="form-select" @disabled($isReadOnly)>
                                    <option value="">-- {{ localize('Select') }} --</option>
                                    @foreach ($menuSaveViewModel->ParentOptions as $parentOption)
                                        <option value="{{ $parentOption['MenuId'] }}" @selected($parentOption['MenuId'] === $menuSaveViewModel->ParentMenuId)>{{ $parentOption['DisplayName'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="MenuCode" class="form-label">{{ localize('Menu code') }}</label>
                                <input id="MenuCode" name="MenuCode" value="{{ $menuSaveViewModel->MenuCode }}" class="form-control text-uppercase" @readonly($isReadOnly) />
                                <div class="form-text">{{ localize('Stable key used in code (MenuCodes). Example: PRODUCTION_DAILY_OUTPUT') }}</div>
                                @include('partials.field-error', ['field' => 'MenuCode'])
                            </div>
                            <div class="col-sm-6">
                                <label for="DefaultName" class="form-label">{{ localize('Default name') }}</label>
                                <input id="DefaultName" name="DefaultName" value="{{ $menuSaveViewModel->DefaultName }}" class="form-control" @readonly($isReadOnly) />
                                @include('partials.field-error', ['field' => 'DefaultName'])
                            </div>
                            <div class="col-sm-6" data-mes-menu-url-field>
                                <label for="Url" class="form-label">{{ localize('URL') }}</label>
                                <input id="Url" name="Url" value="{{ $menuSaveViewModel->Url }}" class="form-control" placeholder="/Production/DailyOutput" @readonly($isReadOnly) />
                                @include('partials.field-error', ['field' => 'Url'])
                            </div>
                            <div class="col-sm-6">
                                <label for="IconClass" class="form-label">{{ localize('Icon') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="width: 40px;"><i data-mes-icon-preview class="{{ $menuSaveViewModel->IconClass }}"></i></span>
                                    <input id="IconClass" name="IconClass" value="{{ $menuSaveViewModel->IconClass }}" class="form-control" placeholder="fa-solid fa-chart-line" data-mes-icon-input @readonly($isReadOnly) />
                                </div>
                                <div class="form-text">
                                    {{ localize('Font Awesome 6 Free class.') }}
                                    <a href="https://fontawesome.com/search?o=r&amp;m=free" target="_blank" rel="noopener">fontawesome.com</a>
                                </div>
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-4">
                                {{-- Checkbox không tick thì trình duyệt không gửi gì -> server hiểu là false. --}}
                                <div class="form-check form-switch">
                                    <input id="IsActive" name="IsActive" type="checkbox" value="true" class="form-check-input" @checked($menuSaveViewModel->IsActive) @disabled($isReadOnly) />
                                    <label for="IsActive" class="form-check-label">{{ localize('Active') }}</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input id="IsVisibleInNavigation" name="IsVisibleInNavigation" type="checkbox" value="true" class="form-check-input" @checked($menuSaveViewModel->IsVisibleInNavigation) @disabled($isReadOnly) />
                                    <label for="IsVisibleInNavigation" class="form-check-label">{{ localize('Show in navigation') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="mes-card">
                    <div class="mes-card-header">
                        <h3 class="mes-card-title">{{ localize('Display name by language') }}</h3>
                    </div>
                    <div class="mes-card-body">
                        @foreach ($menuSaveViewModel->Translations as $translationIndex => $translation)
                            <div class="mb-3">
                                <input type="hidden" name="Translations[{{ $translationIndex }}][LanguageCode]" value="{{ $translation['LanguageCode'] }}" />
                                <input type="hidden" name="Translations[{{ $translationIndex }}][LanguageName]" value="{{ $translation['LanguageName'] }}" />
                                <label for="Translation_{{ $translationIndex }}" class="form-label">{{ $translation['LanguageName'] }}</label>
                                <input id="Translation_{{ $translationIndex }}" name="Translations[{{ $translationIndex }}][DisplayName]" value="{{ $translation['DisplayName'] }}"
                                       class="form-control" @readonly($isReadOnly) />
                            </div>
                        @endforeach
                        <div class="form-text">{{ localize('Leave empty to use the default name.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            @requireAccess('FullAccess')
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ localize('Save') }}</button>
            @endrequireAccess
            <a class="btn btn-light" href="{{ route('system.menu.index') }}" data-mes-dialog-close>{{ localize('Cancel') }}</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/system-menu.js') }}"></script>
@endpush
