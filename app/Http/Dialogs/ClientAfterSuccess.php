<?php

namespace App\Http\Dialogs;

/** = Infrastructure/Dialogs/ClientAfterSuccess.cs: việc mes-ui.js làm sau khi lưu thành công. */
enum ClientAfterSuccess: string
{
    /** Form AJAX nằm trên trang (xóa một dòng, lưu ma trận quyền) - chỉ toast + tải lại vùng. */
    case None = 'None';

    /** Form thêm / sửa trong dialog. */
    case CloseDialog = 'CloseDialog';

    /** Thao tác trong màn hình chi tiết, cần thấy trạng thái mới (duyệt, khóa tài khoản). */
    case ReloadDialog = 'ReloadDialog';

    /** Thay đổi ảnh hưởng Layout: menu, ngôn ngữ, màu thương hiệu. */
    case ReloadPage = 'ReloadPage';
}
