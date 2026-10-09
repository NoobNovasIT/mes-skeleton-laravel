{{--
    = Views/Shared/_DialogLayout.cshtml. Dùng khi View mở trong dialog (request có header X-Mes-Dialog, xem mes_layout()).
    Chỉ render nội dung; tiêu đề lấy từ $title, kích thước từ @section('dialogSize', 'md' | 'lg' | 'xl').
    @push('scripts') của view bị bỏ qua: JavaScript dùng trong dialog phải nằm ở file global
    (mes-ui.js hoặc js/pages/*.js đăng ký qua Mes.ui.registerInitializer).
--}}
<div class="mes-dialog-content" data-mes-dialog-title="{{ $title ?? '' }}" data-mes-dialog-size="@yield('dialogSize', 'lg')">
    @yield('content')
</div>
