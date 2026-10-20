// Xử lý menu responsive trên mobile và tương tác chung cho dashboard
document.addEventListener("DOMContentLoaded", () => {
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");

    function openSidebar() {
        if (sidebar) sidebar.classList.add("is-open");
        if (overlay) overlay.classList.add("is-visible");
        if (menuToggle) menuToggle.setAttribute("aria-expanded", "true");
        document.body.style.overflow = "hidden";
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove("is-open");
        if (overlay) overlay.classList.remove("is-visible");
        if (menuToggle) menuToggle.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";
    }

    if (menuToggle) {
        menuToggle.addEventListener("click", () => {
            const isOpen = sidebar && sidebar.classList.contains("is-open");
            if (isOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeSidebar();
        }
    });

    // Tự động đóng sidebar khi resize lên màn hình lớn (> 768px)
    window.addEventListener("resize", () => {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
});
