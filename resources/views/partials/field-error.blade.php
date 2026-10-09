{{-- = <span asp-validation-for="X">: lỗi của một field. Dùng: @include('partials.field-error', ['field' => 'RoleCode']) --}}
@error($field)
    <span class="field-validation-error">{{ $message }}</span>
@enderror
