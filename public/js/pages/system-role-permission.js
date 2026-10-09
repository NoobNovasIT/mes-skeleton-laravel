/* =============================================================================
   Trang System > Role Permissions (Areas/System/Views/RolePermission/Index.cshtml)
   Nút "Set all" trên Module / Group: đặt cùng một mức quyền cho mọi Page con.

   Dùng event delegation trên document để còn hoạt động khi cây được tải lại
   (chọn Role khác) hoặc khi mở trong dialog từ trang Roles.
   ============================================================================= */
(function (document) {
    "use strict";

    document.addEventListener("click", function (event) {
        var setAccessButton = event.target.closest("[data-mes-set-access]");
        if (!setAccessButton || !setAccessButton.closest("[data-mes-permission-form]")) {
            return;
        }

        var accessLevelValue = setAccessButton.getAttribute("data-mes-set-access");
        var treeItem = setAccessButton.closest("li");

        treeItem.querySelectorAll('input[type="radio"][value="' + accessLevelValue + '"]').forEach(function (radioInput) {
            if (!radioInput.disabled) {
                radioInput.checked = true;
            }
        });
    });
})(document);
