/**
 * FITZONE - DASHBOARD SHARED JS (dashboard.js)
 * Xử lý sidebar responsive, keyboard trap, overlay mobile.
 */

"use strict";

document.addEventListener("DOMContentLoaded", () => {
    const menuToggle = document.getElementById("menuToggle");
    const sidebar    = document.getElementById("sidebar");
    const overlay    = document.getElementById("sidebarOverlay");

    function openSidebar() {
        if (sidebar)    { sidebar.classList.add("is-open"); }
        if (overlay)    { overlay.classList.add("is-visible"); }
        if (menuToggle) { menuToggle.setAttribute("aria-expanded", "true"); }
        document.body.style.overflow = "hidden";
    }

    function closeSidebar() {
        if (sidebar)    { sidebar.classList.remove("is-open"); }
        if (overlay)    { overlay.classList.remove("is-visible"); }
        if (menuToggle) { menuToggle.setAttribute("aria-expanded", "false"); }
        document.body.style.overflow = "";
    }

    if (menuToggle) {
        menuToggle.addEventListener("click", () => {
            const isOpen = sidebar && sidebar.classList.contains("is-open");
            isOpen ? closeSidebar() : openSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeSidebar();
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 768) closeSidebar();
    });
});
