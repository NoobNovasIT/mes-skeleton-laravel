{{-- = Views/Shared/_NotificationScripts.cshtml: toast từ flash session (= TempData) do MesController::operationResponse ghi. --}}
@php
    $successMessage = session(\App\Http\Controllers\MesController::SuccessMessageFlashKey);
    $errorMessage = session(\App\Http\Controllers\MesController::ErrorMessageFlashKey);
@endphp
@if ($successMessage || $errorMessage)
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            @if ($successMessage)
                Mes.notify.success(@json($successMessage));
            @endif
            @if ($errorMessage)
                Mes.notify.error(@json($errorMessage));
            @endif
        });
    </script>
@endif
