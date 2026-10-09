/* =============================================================================
   Trang System > Menus (Areas/System/Views/Menu/Index.cshtml + Save.cshtml)

   1. TreeView menu
      - Thu gọn / mở rộng từng nhánh: dùng chung wwwroot/js/mes-treeview.js.
      - Đổi thứ tự bằng nút mũi tên (trong cùng cấp cha) hoặc kéo-thả SortableJS (đổi được cả cha).
        Module: trái / phải = thứ tự tab trên top bar. Group / Page: lên / xuống = thứ tự trong sidebar.
      - Mọi thay đổi CHỈ trên màn hình cho tới khi bấm "Save order" -> POST MenuController.SavePositions
        [{ menuId, parentMenuId, sortOrder }]. "Discard changes" tải lại cây từ database.
        Server kiểm tra lại quy tắc cấp (Module ở gốc, Page không chứa con) và vòng lặp.
   2. Form thêm / sửa menu (mở trong dialog): ẩn "Menu cha" với Module, chỉ Page có URL, xem trước icon.

   Đăng ký qua Mes.ui.registerInitializer để chạy lại khi cây được tải lại hoặc form mở trong dialog.
   ============================================================================= */
(function (window, document) {
    "use strict";

    var SORT_ORDER_STEP = 10;

    var hasUnsavedChanges = false;

    // ------------------------------------------------------------------ Reorder (arrows + drag & drop)

    function getSiblingMenuItems(listItem) {
        return Array.prototype.filter.call(listItem.parentElement.children, function (childElement) {
            return childElement.matches("li[data-menu-id]");
        });
    }

    /* Nút mũi tên đầu / cuối danh sách anh em bị vô hiệu hóa. */
    function refreshMoveButtons(treeElement) {
        treeElement.querySelectorAll("li[data-menu-id]").forEach(function (listItem) {
            var siblingMenuItems = getSiblingMenuItems(listItem);
            var itemIndex = siblingMenuItems.indexOf(listItem);
            var moveGroup = listItem.querySelector(":scope > .mes-tree-row .mes-tree-move-group");
            if (!moveGroup) {
                return;
            }
            moveGroup.querySelector('[data-mes-menu-move="previous"]').disabled = itemIndex <= 0;
            moveGroup.querySelector('[data-mes-menu-move="next"]').disabled = itemIndex >= siblingMenuItems.length - 1;
        });
    }

    function markMoved(listItem) {
        var rowElement = listItem.querySelector(":scope > .mes-tree-row");
        rowElement.classList.remove("mes-tree-moved");
        void rowElement.offsetWidth; // khởi động lại animation
        rowElement.classList.add("mes-tree-moved");
    }

    function setUnsavedChanges(cardElement, isUnsaved) {
        hasUnsavedChanges = isUnsaved;

        var saveButton = cardElement.querySelector("#saveMenuPositionsButton");
        var discardButton = cardElement.querySelector("#discardMenuPositionsButton");
        var unsavedBadge = cardElement.querySelector("#menuTreeUnsavedBadge");

        if (saveButton) {
            saveButton.disabled = !isUnsaved;
            saveButton.classList.toggle("btn-warning", isUnsaved);
            saveButton.classList.toggle("btn-light", !isUnsaved);
        }
        if (discardButton) {
            discardButton.hidden = !isUnsaved;
        }
        if (unsavedBadge) {
            unsavedBadge.hidden = !isUnsaved;
        }
    }

    document.addEventListener("click", function (event) {
        var moveButton = event.target.closest("[data-mes-menu-move]");
        if (!moveButton || moveButton.disabled) {
            return;
        }

        var listItem = moveButton.closest("li[data-menu-id]");
        var siblingMenuItems = getSiblingMenuItems(listItem);
        var itemIndex = siblingMenuItems.indexOf(listItem);
        var isMovingToPrevious = moveButton.getAttribute("data-mes-menu-move") === "previous";

        if (isMovingToPrevious && itemIndex > 0) {
            listItem.parentElement.insertBefore(listItem, siblingMenuItems[itemIndex - 1]);
        } else if (!isMovingToPrevious && itemIndex < siblingMenuItems.length - 1) {
            listItem.parentElement.insertBefore(listItem, siblingMenuItems[itemIndex + 1].nextSibling);
        } else {
            return;
        }

        var treeElement = listItem.closest("[data-mes-menu-root]");
        refreshMoveButtons(treeElement);
        markMoved(listItem);
        setUnsavedChanges(listItem.closest(".mes-card"), true);
        moveButton.focus();
    });

    window.addEventListener("beforeunload", function (event) {
        if (hasUnsavedChanges) {
            event.preventDefault();
            event.returnValue = "";
        }
    });

    function initializeMenuTree(containerElement) {
        var treeElement = containerElement.querySelector("[data-mes-menu-root]");
        if (!treeElement || treeElement.dataset.mesInitialized === "true") {
            return;
        }
        treeElement.dataset.mesInitialized = "true";

        var cardElement = treeElement.closest(".mes-card");

        refreshMoveButtons(treeElement);
        setUnsavedChanges(cardElement, false);

        var saveButton = cardElement.querySelector("#saveMenuPositionsButton");
        if (!saveButton) {
            return; // user chỉ có quyền xem
        }

        if (window.Sortable) {
            getMenuListElements(treeElement).forEach(function (menuListElement) {
                var isRootList = menuListElement === treeElement;

                new window.Sortable(menuListElement, {
                    // Chặn ngay khi kéo: cấp gốc chỉ nhận Module, cấp con không nhận Module.
                    // Server vẫn kiểm tra lại quy tắc cấp khi Save order.
                    group: {
                        name: "mes-menu-tree",
                        put: function (toSortable, fromSortable, draggedElement) {
                            var isModule = draggedElement.getAttribute("data-menu-type") === "Module";
                            return isRootList ? isModule : !isModule;
                        }
                    },
                    handle: ".mes-tree-handle",
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    onEnd: function (sortableEvent) {
                        window.Mes.treeview.refreshDepths(treeElement);
                        refreshMoveButtons(treeElement);
                        markMoved(sortableEvent.item);
                        setUnsavedChanges(cardElement, true);
                    }
                });
            });
        }

        saveButton.addEventListener("click", function () {
            saveButton.disabled = true;

            window.Mes.postJson(saveButton.getAttribute("data-save-url"), collectMenuPositions(treeElement)).then(function (response) {
                if (response.isSuccess) {
                    hasUnsavedChanges = false;
                    window.Mes.notify.success(response.message);
                    // Sidebar / tab Module thay đổi theo thứ tự mới -> tải lại cả trang.
                    window.setTimeout(function () { window.location.reload(); }, 800);
                } else {
                    window.Mes.notify.error(response.message);
                    saveButton.disabled = false;
                }
            });
        });

        cardElement.querySelector("#discardMenuPositionsButton").addEventListener("click", function () {
            hasUnsavedChanges = false;
            window.location.reload();
        });
    }

    /* Mọi danh sách menu trong cây, GỒM CẢ <ul> gốc (cấp Module).
       querySelectorAll chỉ trả phần tử con, không tính chính treeElement - thiếu dòng concat
       thì thứ tự Module (tab trên topbar) không bao giờ được gửi lên server. */
    function getMenuListElements(treeElement) {
        return [treeElement].concat(Array.prototype.slice.call(treeElement.querySelectorAll("[data-mes-menu-list]")));
    }

    function collectMenuPositions(treeElement) {
        var menuPositions = [];

        getMenuListElements(treeElement).forEach(function (menuListElement) {
            var parentListItem = menuListElement.parentElement.closest("li[data-menu-id]");
            var parentMenuId = parentListItem ? parseInt(parentListItem.getAttribute("data-menu-id"), 10) : null;

            getSiblingMenuItemsOfList(menuListElement).forEach(function (menuListItem, itemIndex) {
                menuPositions.push({
                    menuId: parseInt(menuListItem.getAttribute("data-menu-id"), 10),
                    parentMenuId: parentMenuId,
                    sortOrder: (itemIndex + 1) * SORT_ORDER_STEP
                });
            });
        });

        return menuPositions;
    }

    function getSiblingMenuItemsOfList(menuListElement) {
        return Array.prototype.filter.call(menuListElement.children, function (childElement) {
            return childElement.matches("li[data-menu-id]");
        });
    }
    // ------------------------------------------------------------------ Menu form (create / edit)

    function refreshMenuFormFields(menuTypeSelect) {
        var formElement = menuTypeSelect.closest("form");
        var parentMenuField = formElement.querySelector("[data-mes-menu-parent-field]");
        var urlField = formElement.querySelector("[data-mes-menu-url-field]");

        if (parentMenuField) {
            parentMenuField.hidden = menuTypeSelect.value === "Module";
        }
        if (urlField) {
            urlField.hidden = menuTypeSelect.value !== "Page";
        }
    }

    function initializeMenuForm(containerElement) {
        containerElement.querySelectorAll("select[data-mes-menu-type-select]").forEach(refreshMenuFormFields);
    }

    document.addEventListener("change", function (event) {
        if (event.target.matches("select[data-mes-menu-type-select]")) {
            refreshMenuFormFields(event.target);
        }
    });

    document.addEventListener("input", function (event) {
        if (event.target.matches("input[data-mes-icon-input]")) {
            var iconPreview = event.target.closest(".input-group").querySelector("[data-mes-icon-preview]");
            if (iconPreview) {
                iconPreview.className = event.target.value;
            }
        }
    });

    window.Mes.ui.registerInitializer(function (containerElement) {
        initializeMenuTree(containerElement);
        initializeMenuForm(containerElement);
    });
})(window, document);
