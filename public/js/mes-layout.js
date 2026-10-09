/* =============================================================================
   MES layout - hành vi của app shell (Views/Shared/_Layout.cshtml)
     - Thu gọn / mở rộng sidebar (desktop), nhớ trạng thái trong localStorage
     - Drawer sidebar (mobile < 768px)
     - Mở / đóng Group trong sidebar
     - Đổi ngôn ngữ
   ============================================================================= */
(function (window, document) {
    "use strict";

    var SIDEBAR_COLLAPSED_STORAGE_KEY = "mes.sidebar.collapsed";

    var shellElement = document.getElementById("mesShell");
    if (!shellElement) {
        return;
    }

    function readStorage(key) {
        try { return window.localStorage.getItem(key); } catch (error) { return null; }
    }

    function writeStorage(key, value) {
        try { window.localStorage.setItem(key, value); } catch (error) { /* private mode */ }
    }

    // --- Collapse (desktop)
    var collapseButton = document.getElementById("mesSidebarCollapseButton");
    if (collapseButton) {
        collapseButton.addEventListener("click", function () {
            var isCollapsed = shellElement.classList.toggle("mes-collapsed");
            writeStorage(SIDEBAR_COLLAPSED_STORAGE_KEY, isCollapsed ? "1" : "0");
        });
    }

    // --- Drawer (mobile)
    var drawerButton = document.getElementById("mesDrawerButton");
    var backdropElement = document.getElementById("mesBackdrop");
    if (drawerButton) {
        drawerButton.addEventListener("click", function () {
            shellElement.classList.add("mes-drawer-open");
        });
    }
    if (backdropElement) {
        backdropElement.addEventListener("click", function () {
            shellElement.classList.remove("mes-drawer-open");
        });
    }

    // --- Group toggle
    document.querySelectorAll("[data-mes-group-toggle]").forEach(function (toggleButton) {
        toggleButton.addEventListener("click", function () {
            // Sidebar đang thu gọn: bấm vào icon Group thì mở rộng sidebar ra trước.
            if (shellElement.classList.contains("mes-collapsed") && window.innerWidth >= 768) {
                shellElement.classList.remove("mes-collapsed");
                writeStorage(SIDEBAR_COLLAPSED_STORAGE_KEY, "0");
            }

            var groupElement = toggleButton.closest(".mes-nav-group");
            var isOpen = groupElement.classList.toggle("mes-open");
            toggleButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });
    });

    // --- Scroll active item into view
    var activeLink = document.querySelector(".mes-sidebar a.mes-nav-link.mes-active");
    if (activeLink && activeLink.scrollIntoView) {
        activeLink.scrollIntoView({ block: "nearest" });
    }
})(window, document);
