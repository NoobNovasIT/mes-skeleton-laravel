/* =============================================================================
   MES UI - dialog, form AJAX, vùng tải lại. Load trong _Layout.cshtml sau mes-core.js.

   Thuộc tính HTML (không cần viết JavaScript riêng cho từng page):

   <a href="/System/Role/Create" data-mes-dialog>                 Mở URL trong dialog (Bootstrap modal).
                                                                   Link nằm trong dialog -> thay nội dung dialog.
   <a href="/System/Role" data-mes-dialog-close>                  Trong dialog: đóng dialog. Ngoài dialog: link bình thường.
   <form method="post" data-mes-ajax-form>                        Submit bằng AJAX (form trong dialog tự động AJAX).
   <div data-mes-refresh-region="role-list">                      Vùng được tải lại sau khi lưu thành công.
   <a href="?pageNumber=2" data-mes-region-link="role-list">      Tải trang khác nhưng chỉ thay vùng (phân trang).
   <form method="get" data-mes-region-form="role-list">           Form lọc GET chỉ thay vùng.
   <table data-mes-datatable>                                     Tự khởi tạo DataTables.

   Server (MesControllerBase.OperationResponse) trả JSON:
     { isSuccess, message, afterSuccess: "None" | "CloseDialog" | "ReloadDialog" | "ReloadPage" }
   Validation lỗi -> server trả lại HTML form -> thay nội dung dialog.

   Code riêng của page cần chạy lại khi nội dung mới được chèn (dialog, vùng tải lại):
     Mes.ui.registerInitializer(function (containerElement) { ... });
   ============================================================================= */
(function (window, document, $) {
    "use strict";

    var texts = window.MesTexts || {};
    var initializers = [];

    var dialogElement = document.getElementById("mesDialog");
    var dialogTitleElement = document.getElementById("mesDialogTitle");
    var dialogBodyElement = document.getElementById("mesDialogBody");
    var dialogSizeElement = dialogElement ? dialogElement.querySelector(".modal-dialog") : null;
    var bootstrapModal = dialogElement && window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(dialogElement) : null;
    var currentDialogUrl = null;

    // ------------------------------------------------------------------ Initializers

    function registerInitializer(initializer) {
        initializers.push(initializer);
        // Script đăng ký sau khi trang đã tải xong -> chạy ngay; ngược lại DOMContentLoaded sẽ chạy.
        if (document.readyState !== "loading") {
            initializer(document);
        }
    }

    function initializeContainer(containerElement) {
        if ($ && $.validator && $.validator.unobtrusive) {
            $(containerElement).find("form").each(function () {
                $(this).removeData("validator").removeData("unobtrusiveValidation");
            });
            $.validator.unobtrusive.parse(containerElement);
        }

        initializers.forEach(function (initializer) { initializer(containerElement); });
    }

    // DataTables cho mọi bảng có data-mes-datatable.
    registerInitializer(function (containerElement) {
        if (!window.DataTable) {
            return;
        }
        containerElement.querySelectorAll("table[data-mes-datatable]").forEach(function (tableElement) {
            if (!tableElement.classList.contains("dataTable")) {
                new window.DataTable(tableElement, { pageLength: 25, order: [] });
            }
        });
    });

    // ------------------------------------------------------------------ HTTP helpers

    function buildHeaders(isDialogRequest) {
        var headers = {
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": window.Mes.getAntiforgeryToken()
        };
        if (isDialogRequest) {
            headers["X-Mes-Dialog"] = "true";
        }
        return headers;
    }

    /* Đọc response: JSON -> object, HTML -> { html }. Lỗi HTTP -> { isSuccess: false, message }. */
    function readResponse(response) {
        if (response.status === 401) {
            window.location.reload();
            return Promise.resolve({ isSuccess: false, message: "" });
        }

        var contentType = response.headers.get("Content-Type") || "";
        if (contentType.indexOf("application/json") >= 0) {
            return response.json();
        }

        if (!response.ok) {
            return Promise.resolve({ isSuccess: false, message: texts.unexpectedError || "An unexpected error occurred." });
        }

        return response.text().then(function (html) { return { html: html }; });
    }

    function handleNetworkError() {
        return { isSuccess: false, message: texts.networkError || "Cannot connect to the server." };
    }

    // ------------------------------------------------------------------ Dialog

    function setDialogSize(dialogSize) {
        dialogSizeElement.classList.remove("modal-lg", "modal-xl");
        if (dialogSize === "lg" || dialogSize === "xl") {
            dialogSizeElement.classList.add("modal-" + dialogSize);
        }
    }

    function showDialogLoading() {
        dialogBodyElement.innerHTML = '<div class="mes-dialog-loading"><div class="spinner-border text-primary" role="status"></div></div>';
    }

    function renderDialogHtml(html) {
        dialogBodyElement.innerHTML = html;

        var dialogContentElement = dialogBodyElement.querySelector(".mes-dialog-content");
        if (dialogContentElement) {
            dialogTitleElement.textContent = dialogContentElement.getAttribute("data-mes-dialog-title") || "";
            setDialogSize(dialogContentElement.getAttribute("data-mes-dialog-size"));
        }

        initializeContainer(dialogBodyElement);

        var firstInput = dialogBodyElement.querySelector("input:not([type=hidden]):not([readonly]):not([disabled]), select, textarea");
        if (firstInput) {
            window.setTimeout(function () { firstInput.focus(); }, 200);
        }
    }

    function openDialog(url) {
        if (!bootstrapModal) {
            window.location.href = url;
            return Promise.resolve();
        }

        currentDialogUrl = url;
        var isAlreadyOpen = dialogElement.classList.contains("show");
        if (!isAlreadyOpen) {
            dialogTitleElement.textContent = "";
            setDialogSize("lg");
            showDialogLoading();
            bootstrapModal.show();
        }

        return fetch(url, { credentials: "same-origin", headers: buildHeaders(true) })
            .then(readResponse, handleNetworkError)
            .then(function (result) {
                if (result.html !== undefined) {
                    renderDialogHtml(result.html);
                    return;
                }

                // JSON khi mở dialog = bị chặn quyền / lỗi.
                window.Mes.notify.error(result.message);
                if (!isAlreadyOpen) {
                    bootstrapModal.hide();
                }
            });
    }

    function closeDialog() {
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    }

    function reloadDialog() {
        return currentDialogUrl ? openDialog(currentDialogUrl) : Promise.resolve();
    }

    if (dialogElement) {
        dialogElement.addEventListener("hidden.bs.modal", function () {
            dialogBodyElement.innerHTML = "";
            currentDialogUrl = null;
        });
    }

    // ------------------------------------------------------------------ Refresh regions

    /* Tải lại URL (mặc định trang hiện tại) và thay các vùng data-mes-refresh-region cùng tên. */
    function refreshRegions(url, regionNames) {
        var regionElements = Array.prototype.slice.call(document.querySelectorAll("[data-mes-refresh-region]"))
            .filter(function (regionElement) {
                return !regionNames || regionNames.indexOf(regionElement.getAttribute("data-mes-refresh-region")) >= 0;
            });

        if (regionElements.length === 0) {
            return Promise.resolve(false);
        }

        return fetch(url || window.location.href, { credentials: "same-origin" })
            .then(function (response) { return response.ok ? response.text() : null; })
            .then(function (html) {
                if (html === null) {
                    return false;
                }

                var newDocument = new DOMParser().parseFromString(html, "text/html");
                var isAllRegionFound = true;

                regionElements.forEach(function (regionElement) {
                    var regionName = regionElement.getAttribute("data-mes-refresh-region");
                    var newRegionElement = newDocument.querySelector('[data-mes-refresh-region="' + regionName + '"]');
                    if (!newRegionElement) {
                        isAllRegionFound = false;
                        return;
                    }
                    regionElement.innerHTML = newRegionElement.innerHTML;
                    initializeContainer(regionElement);
                });

                // Trang trả về không có vùng (hết phiên -> trang đăng nhập...) -> tải lại cả trang.
                if (!isAllRegionFound) {
                    window.location.reload();
                }
                return true;
            })
            .catch(function () { return false; });
    }

    function navigateRegion(url, regionName) {
        return refreshRegions(url, [regionName]).then(function (isRefreshed) {
            if (isRefreshed) {
                window.history.pushState({ mesRegion: regionName }, "", url);
            } else {
                window.location.href = url;
            }
        });
    }

    window.addEventListener("popstate", function (event) {
        if (event.state && event.state.mesRegion) {
            refreshRegions(window.location.href, [event.state.mesRegion]);
        } else {
            window.location.reload();
        }
    });

    // ------------------------------------------------------------------ Form submission

    function setFormBusy(formElement, isBusy) {
        formElement.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (submitButton) {
            submitButton.disabled = isBusy;
        });
    }

    function handleSubmitResult(formElement, isInsideDialog, result) {
        if (result.html !== undefined) {
            if (isInsideDialog) {
                renderDialogHtml(result.html);
            } else {
                window.location.reload();
            }
            return;
        }

        if (!result.isSuccess) {
            window.Mes.notify.error(result.message);
            return;
        }

        window.Mes.notify.success(result.message);

        switch (result.afterSuccess) {
            case "ReloadPage":
                window.setTimeout(function () { window.location.reload(); }, 600);
                return;
            case "ReloadDialog":
                reloadDialog();
                break;
            case "CloseDialog":
                closeDialog();
                break;
        }

        refreshRegions();
    }

    function submitFormWithAjax(formElement, submitterElement) {
        var isInsideDialog = dialogBodyElement !== null && dialogBodyElement.contains(formElement);

        if ($ && $.fn.valid && $(formElement).data("validator") && !$(formElement).valid()) {
            return;
        }

        var formData = new FormData(formElement);
        if (submitterElement && submitterElement.name) {
            formData.append(submitterElement.name, submitterElement.value);
        }

        setFormBusy(formElement, true);

        fetch(formElement.action || window.location.href, {
            method: "POST",
            credentials: "same-origin",
            headers: buildHeaders(isInsideDialog),
            body: formData
        })
            .then(readResponse, handleNetworkError)
            .then(function (result) { handleSubmitResult(formElement, isInsideDialog, result); })
            .finally(function () { setFormBusy(formElement, false); });
    }

    // Bubbling phase: chạy SAU bước hỏi xác nhận (data-mes-confirm) của mes-core.js.
    document.addEventListener("submit", function (event) {
        var formElement = event.target;
        if (!(formElement instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        var method = (formElement.getAttribute("method") || "get").toLowerCase();
        var regionName = formElement.getAttribute("data-mes-region-form");

        if (method === "get" && regionName) {
            event.preventDefault();
            var queryString = new URLSearchParams(new FormData(formElement)).toString();
            navigateRegion((formElement.action || window.location.pathname).split("?")[0] + "?" + queryString, regionName);
            return;
        }

        var isInsideDialog = dialogBodyElement !== null && dialogBodyElement.contains(formElement);
        if (method === "post" && (isInsideDialog || formElement.hasAttribute("data-mes-ajax-form"))) {
            event.preventDefault();
            submitFormWithAjax(formElement, event.submitter);
        }
    });

    // ------------------------------------------------------------------ Links

    document.addEventListener("click", function (event) {
        var linkElement = event.target.closest("a[data-mes-dialog], a[data-mes-dialog-close], a[data-mes-region-link]");
        if (!linkElement || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) {
            return;
        }

        if (linkElement.hasAttribute("data-mes-dialog-close")) {
            if (dialogBodyElement && dialogBodyElement.contains(linkElement)) {
                event.preventDefault();
                closeDialog();
            }
            return;
        }

        if (linkElement.hasAttribute("data-mes-dialog")) {
            event.preventDefault();
            openDialog(linkElement.getAttribute("href"));
            return;
        }

        event.preventDefault();
        navigateRegion(linkElement.getAttribute("href"), linkElement.getAttribute("data-mes-region-link"));
    });

    document.addEventListener("DOMContentLoaded", function () { initializeContainer(document); });

    window.Mes = window.Mes || {};
    window.Mes.dialog = { open: openDialog, close: closeDialog, reload: reloadDialog };
    window.Mes.ui = { registerInitializer: registerInitializer, refreshRegions: refreshRegions, initializeContainer: initializeContainer };
})(window, document, window.jQuery);
