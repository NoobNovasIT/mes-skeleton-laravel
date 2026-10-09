{{-- = Views/Shared/_ClientTexts.cshtml: chuỗi đã dịch cho JavaScript (public/js/mes-core.js đọc window.MesTexts). --}}
@php
    $clientTexts = [
        'confirmTitle' => localize('Are you sure?'),
        'confirmButton' => localize('Confirm'),
        'cancelButton' => localize('Cancel'),
        'unexpectedError' => localize('An unexpected error occurred. Please try again or contact the administrator.'),
        'networkError' => localize('Cannot connect to the server.'),
    ];
@endphp
<script>
    window.MesTexts = {{ Illuminate\Support\Js::from($clientTexts) }};
</script>
