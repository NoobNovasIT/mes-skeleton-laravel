/* =============================================================================
   MES treeview - thu gọn / mở rộng cây dùng chung.
   Đang dùng ở: System > Menus, System > Role Permissions, User > Permission overrides.

   HTML cần có:
     <ul class="mes-treeview" data-mes-tree-root data-mes-tree-storage-key="mes.xxx.collapsedMenuIds">
       <li class="mes-treeview-item" data-menu-id="1">
         <div class="mes-tree-row">
           <button data-mes-tree-toggle aria-expanded="true">...</button>
         </div>
         <ul class="mes-treeview-children"> ... </ul>
       </li>
     </ul>

   Nút Expand all / Collapse all: [data-mes-tree-expand-all] / [data-mes-tree-collapse-all]
   đặt trong cùng .mes-card với cây.

   Trạng thái thu gọn nhớ trong localStorage theo khóa data-mes-tree-storage-key
   (không khai báo khóa thì không nhớ). Load trong _Layout.cshtml, sau mes-ui.js.
   ============================================================================= */
(function (window, document) {
    "use strict";

    var COLLAPSED_CLASS = "mes-tree-collapsed";

    function readCollapsedMenuIds(storageKey) {
        if (!storageKey) {
            return [];
        }
        try {
            return JSON.parse(window.localStorage.getItem(storageKey) || "[]");
        } catch (error) {
            return [];
        }
    }

    function writeCollapsedMenuIds(treeElement) {
        if (!treeElement) {
            return;
        }

        var storageKey = treeElement.getAttribute("data-mes-tree-storage-key");
        if (!storageKey) {
            return;
        }

        var collapsedMenuIds = Array.prototype.map.call(
            treeElement.querySelectorAll("li." + COLLAPSED_CLASS),
            function (listItem) { return listItem.getAttribute("data-menu-id"); });

        try {
            window.localStorage.setItem(storageKey, JSON.stringify(collapsedMenuIds));
        } catch (error) { /* trình duyệt ở chế độ ẩn danh */ }
    }

    function setCollapsed(listItem, isCollapsed) {
        listItem.classList.toggle(COLLAPSED_CLASS, isCollapsed);

        var toggleButton = listItem.querySelector(":scope > .mes-tree-row [data-mes-tree-toggle]");
        if (toggleButton) {
            toggleButton.setAttribute("aria-expanded", isCollapsed ? "false" : "true");
        }
    }

    document.addEventListener("click", function (event) {
        var toggleButton = event.target.closest("[data-mes-tree-toggle]");
        if (toggleButton) {
            var listItem = toggleButton.closest("li[data-menu-id]");
            if (!listItem) {
                return;
            }
            setCollapsed(listItem, !listItem.classList.contains(COLLAPSED_CLASS));
            writeCollapsedMenuIds(listItem.closest("[data-mes-tree-root]"));
            return;
        }

        var expandCollapseButton = event.target.closest("[data-mes-tree-expand-all], [data-mes-tree-collapse-all]");
        if (!expandCollapseButton) {
            return;
        }

        var containerElement = expandCollapseButton.closest(".mes-card") || document;
        var isCollapsing = expandCollapseButton.hasAttribute("data-mes-tree-collapse-all");

        containerElement.querySelectorAll("[data-mes-tree-root]").forEach(function (treeElement) {
            treeElement.querySelectorAll("li[data-menu-id]").forEach(function (listItem) {
                if (listItem.querySelector(":scope > .mes-tree-row [data-mes-tree-toggle]")) {
                    setCollapsed(listItem, isCollapsing);
                }
            });
            writeCollapsedMenuIds(treeElement);
        });
    });

    /* Khôi phục trạng thái đã nhớ - chạy lại khi vùng được tải lại hoặc cây mở trong dialog. */
    function applyCollapsedState(containerElement) {
        containerElement.querySelectorAll("[data-mes-tree-root]").forEach(function (treeElement) {
            var collapsedMenuIds = readCollapsedMenuIds(treeElement.getAttribute("data-mes-tree-storage-key"));
            if (collapsedMenuIds.length === 0) {
                return;
            }

            treeElement.querySelectorAll("li[data-menu-id]").forEach(function (listItem) {
                if (collapsedMenuIds.indexOf(listItem.getAttribute("data-menu-id")) >= 0) {
                    setCollapsed(listItem, true);
                }
            });
        });
    }

    /* Ghi lại --mes-tree-depth theo vị trí thực trong DOM. Razor đã ghi sẵn lúc render;
       chỉ cần gọi lại khi node bị kéo sang cấp khác (trang Menus) để cột chip / mã vẫn thẳng hàng. */
    function refreshDepths(treeElement) {
        treeElement.querySelectorAll("li[data-menu-id]").forEach(function (listItem) {
            var depth = 0;
            var ancestorItem = listItem.parentElement.closest("li[data-menu-id]");
            while (ancestorItem && treeElement.contains(ancestorItem)) {
                depth++;
                ancestorItem = ancestorItem.parentElement.closest("li[data-menu-id]");
            }
            listItem.style.setProperty("--mes-tree-depth", depth);
        });
    }

    window.Mes.ui.registerInitializer(applyCollapsedState);
    window.Mes.treeview = { applyCollapsedState: applyCollapsedState, setCollapsed: setCollapsed, refreshDepths: refreshDepths };
})(window, document);
