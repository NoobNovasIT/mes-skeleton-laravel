{{-- = Areas/System/Views/SystemSetting/Index.cshtml. Lưu AJAX -> tải lại cả trang (branding đổi). --}}
@extends('layouts.app')

@use('App\Support\Constants\SystemSettingKeys')
@php
    $isReadOnly = ! \App\Http\Permissions\PagePermission::current()->canWrite();
    $getInputType = fn (string $settingKey) => match (true) {
        $settingKey === SystemSettingKeys::BrandingPrimaryColor => 'color',
        $settingKey === SystemSettingKeys::RegistrationIsSelfRegistrationEnabled => 'boolean',
        str_starts_with($settingKey, 'Security.') => 'number',
        default => 'text',
    };
@endphp

@section('content')
    <form method="post" action="{{ route('system.system-setting.save') }}" data-mes-ajax-form>
        @csrf
        @foreach (collect($systemSettings)->groupBy('settingGroup') as $settingGroup => $settings)
            <div class="mes-card">
                <div class="mes-card-header">
                    <h3 class="mes-card-title">{{ localize($settingGroup) }}</h3>
                </div>
                <div class="mes-card-body mes-card-body-flush">
                    <table class="table">
                        <tbody>
                            @foreach ($settings as $setting)
                                @php
                                    $inputId = 'setting_'.str_replace('.', '_', $setting->settingKey);
                                    $inputName = "settingValues[{$setting->settingKey}]";
                                @endphp
                                <tr>
                                    <td style="width: 38%;">
                                        <label class="form-label mb-0" for="{{ $inputId }}">{{ localize($setting->description ?? $setting->settingKey) }}</label>
                                        <div class="mes-tree-meta">{{ $setting->settingKey }}</div>
                                    </td>
                                    <td>
                                        @switch($getInputType($setting->settingKey))
                                            @case('color')
                                                <input type="color" class="form-control form-control-color" id="{{ $inputId }}" name="{{ $inputName }}" value="{{ $setting->settingValue }}" @disabled($isReadOnly) />
                                                @break
                                            @case('boolean')
                                                <select class="form-select" id="{{ $inputId }}" name="{{ $inputName }}" style="max-width: 160px;" @disabled($isReadOnly)>
                                                    <option value="true" @selected($setting->settingValue === 'true')>{{ localize('Yes') }}</option>
                                                    <option value="false" @selected($setting->settingValue === 'false')>{{ localize('No') }}</option>
                                                </select>
                                                @break
                                            @case('number')
                                                <input type="number" min="1" class="form-control" id="{{ $inputId }}" name="{{ $inputName }}" value="{{ $setting->settingValue }}" style="max-width: 160px;" @readonly($isReadOnly) />
                                                @break
                                            @default
                                                <input type="text" class="form-control" id="{{ $inputId }}" name="{{ $inputName }}" value="{{ $setting->settingValue }}" maxlength="1000" @readonly($isReadOnly) />
                                        @endswitch
                                    </td>
                                    <td class="text-body-secondary small text-nowrap d-none d-md-table-cell">{{ mes_date($setting->updatedAt) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        @requireAccess('FullAccess')
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ localize('Save') }}</button>
        @endrequireAccess
    </form>
@endsection
