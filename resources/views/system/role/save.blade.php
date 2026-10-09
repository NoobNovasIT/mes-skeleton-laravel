{{-- = Areas/System/Views/Role/Save.cshtml - dùng chung Create + Edit. --}}
@extends(mes_layout())
@section('dialogSize', 'md')

@php
    $isReadOnly = ! \App\Http\Permissions\PagePermission::current()->canWrite();
    $formAction = $roleSaveViewModel->isCreateMode() ? route('system.role.create') : route('system.role.edit', $roleSaveViewModel->RoleId);
@endphp

@section('content')
    <div class="row">
        <div class="{{ request()->isDialogRequest() ? 'col-12' : 'col-lg-7 col-xl-6' }}">
            <div class="mes-card">
                <form method="post" action="{{ $formAction }}" novalidate>
                    @csrf
                    <div class="mes-card-body">
                        @include('partials.validation-summary')

                        <div class="mb-3">
                            <label for="RoleCode" class="form-label">{{ localize('Role code') }}</label>
                            <input id="RoleCode" name="RoleCode" value="{{ $roleSaveViewModel->RoleCode }}" class="form-control text-uppercase"
                                   @readonly($roleSaveViewModel->IsSystemRole || $isReadOnly) />
                            <div class="form-text">{{ localize('Uppercase letters, digits and underscore. Example: PRODUCTION_LEADER') }}</div>
                            @include('partials.field-error', ['field' => 'RoleCode'])
                        </div>
                        <div class="mb-3">
                            <label for="RoleName" class="form-label">{{ localize('Role name') }}</label>
                            <input id="RoleName" name="RoleName" value="{{ $roleSaveViewModel->RoleName }}" class="form-control" @readonly($isReadOnly) />
                            @include('partials.field-error', ['field' => 'RoleName'])
                        </div>
                        <div class="mb-1">
                            <label for="Description" class="form-label">{{ localize('Description') }}</label>
                            <textarea id="Description" name="Description" class="form-control" rows="3" @readonly($isReadOnly)>{{ $roleSaveViewModel->Description }}</textarea>
                            @include('partials.field-error', ['field' => 'Description'])
                        </div>
                    </div>
                    <div class="mes-card-footer">
                        @requireAccess('FullAccess')
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> {{ localize('Save') }}</button>
                        @endrequireAccess
                        <a class="btn btn-light" href="{{ route('system.role.index') }}" data-mes-dialog-close>{{ localize('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
