/* =============================================================================
   MES core - tiện ích JavaScript dùng chung cho mọi page.
   Load trong _Layout.cshtml và _AuthLayout.cshtml, sau jQuery + SweetAlert2.

     Mes.notify.success(message) / Mes.notify.error(message) / Mes.notify.info(message)
     Mes.confirm({ title, text, confirmText, cancelText, isDanger })  -> Promise<boolean>
     Mes.postJson(url, data)                                           -> Promise<{ isSuccess, message, ... }>

   Form có thuộc tính data-mes-confirm="Câu hỏi" sẽ hỏi xác nhận trước khi submit:
     <form method="post" asp-action="Delete" data-mes-confirm="@Localizer["Delete this role?"]">
   ============================================================================= */
(function (window, $) {
    "use strict";

    var texts = window.MesTexts || {};

    /* Bản Laravel: token lấy từ csrf_token() (meta trong layout), gửi qua header X-CSRF-TOKEN
       - middleware VerifyCsrfToken của Laravel đọc header này (bên ASP.NET là RequestVerificationToken). */
    function getAntiforgeryToken() {
        var meta = document.querySelector('meta[name="mes-antiforgery-token"]');
        return meta ? meta.getAttribute("content") : "";
    }

    var toast = window.Swal
        ? window.Swal.mixin({
            toast: true,
            position: "bottom-end",
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        })
        : null;

    var notify = {
        success: function (message) { showToast("success", message); },
        error: function (message) { showToast("error", message); },
        info: function (message) { showToast("info", message); },
        /* Toast tùy biến: { icon, title, text, timer, onClick } - dùng cho thông báo realtime bấm vào để mở. */
        show: showCustomToast
    };

    function showCustomToast(options) {
        if (!toast) {
            return;
        }

        toast.fire({
            icon: options.icon || "info",
            title: options.title || "",
            text: options.text || "",
            timer: options.timer || 3500,
            customClass: { popup: options.onClick ? "mes-toast-clickable" : "" },
            didOpen: function (popupElement) {
                popupElement.addEventListener("mouseenter", window.Swal.stopTimer);
                popupElement.addEventListener("mouseleave", window.Swal.resumeTimer);
                if (options.onClick) {
                    popupElement.addEventListener("click", function () {
                        window.Swal.close();
                        options.onClick();
                    });
                }
            }
        });
    }

    function showToast(icon, message) {
        if (!message) {
            return;
        }
        if (toast) {
            toast.fire({ icon: icon, title: message });
        } else {
            window.alert(message);
        }
    }

    function confirmAction(options) {
        options = options || {};
        if (!window.Swal) {
            return Promise.resolve(window.confirm(options.text || options.title));
        }

        return window.Swal.fire({
            title: options.title || texts.confirmTitle || "Are you sure?",
            text: options.text || "",
            icon: options.isDanger ? "warning" : "question",
            showCancelButton: true,
            focusCancel: true,
            confirmButtonText: options.confirmText || texts.confirmButton || "OK",
            cancelButtonText: options.cancelText || texts.cancelButton || "Cancel",
            customClass: {
                confirmButton: options.isDanger ? "btn btn-danger" : "btn btn-primary",
                cancelButton: "btn btn-light ms-2"
            },
            buttonsStyling: false
        }).then(function (result) { return result.isConfirmed === true; });
    }

    /* POST JSON kèm antiforgery token. Luôn resolve với object { isSuccess, message }. */
    function postJson(url, data) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": getAntiforgeryToken()
            },
            body: JSON.stringify(data || {})
        }).then(function (response) {
            if (response.status === 401) {
                window.location.reload();
                return { isSuccess: false, message: "" };
            }
            return response.json().catch(function () {
                return { isSuccess: false, message: texts.unexpectedError || "An unexpected error occurred." };
            });
        }).catch(function () {
            return { isSuccess: false, message: texts.networkError || "Cannot connect to the server." };
        });
    }

    /* Mọi request jQuery.ajax cũng mang antiforgery token. */
    if ($) {
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": getAntiforgeryToken(),
                "X-Requested-With": "XMLHttpRequest"
            }
        });
    }

    /* Form cần xác nhận trước khi submit (capture phase - chạy trước xử lý AJAX của mes-ui.js).
       Sau khi xác nhận dùng requestSubmit() để sự kiện submit phát lại, form AJAX / dialog vẫn hoạt động. */
    document.addEventListener("submit", function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute("data-mes-confirm")) {
            return;
        }

        if (form.dataset.mesConfirmed === "true") {
            delete form.dataset.mesConfirmed;
            return;
        }

        event.preventDefault();
        var submitterElement = event.submitter;
        confirmAction({
            title: form.getAttribute("data-mes-confirm"),
            isDanger: form.hasAttribute("data-mes-danger")
        }).then(function (isConfirmed) {
            if (isConfirmed) {
                form.dataset.mesConfirmed = "true";
                if (form.requestSubmit) {
                    form.requestSubmit(submitterElement && submitterElement.form === form ? submitterElement : undefined);
                } else {
                    form.submit();
                }
            }
        });
    }, true);

    /* Bộ chọn ngôn ngữ (<details data-mes-language-switcher>) tự đóng khi bấm ra ngoài hoặc nhấn Esc.
       Dùng <details> vì thẻ <option> của <select> không hiển thị được ảnh cờ, và trang đăng nhập
       không load Bootstrap JS nên không dùng được dropdown của Bootstrap. */
    function closeLanguageSwitchers(exceptElement) {
        document.querySelectorAll("details[data-mes-language-switcher][open]").forEach(function (switcherElement) {
            if (switcherElement !== exceptElement) {
                switcherElement.open = false;
            }
        });
    }

    document.addEventListener("click", function (event) {
        closeLanguageSwitchers(event.target.closest("details[data-mes-language-switcher]"));
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeLanguageSwitchers(null);
        }
    });

    window.Mes = {
        notify: notify,
        confirm: confirmAction,
        postJson: postJson,
        getAntiforgeryToken: getAntiforgeryToken
    };
})(window, window.jQuery);
