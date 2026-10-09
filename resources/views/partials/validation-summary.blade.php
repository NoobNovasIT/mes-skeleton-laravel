{{-- = <div asp-validation-summary="ModelOnly">: lỗi nghiệp vụ ở đầu form (MesController::SummaryErrorKey). --}}
@error('summary')
    <div class="alert alert-danger py-2">{{ $message }}</div>
@enderror
