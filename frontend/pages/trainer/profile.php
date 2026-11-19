<?php

require_once __DIR__ . '/../../../backend/helpers/auth.php';

/* Bảo vệ trang - phải đăng nhập */
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$role      = $_SESSION['role'] ?? 'member';
$csrfToken = getCsrfToken();

/* Biến layout dùng chung */
$pageTitle   = "Thông tin cá nhân";
$sidebarRole = $role;
$activeNav   = 'profile';
$topbarLabel = match ($role) {
    'admin'   => 'ADMIN AREA',
    'staff'   => 'STAFF AREA',
    'trainer' => 'TRAINER AREA',
    default   => 'MEMBER AREA',
};
$topbarTitle = 'Thông tin cá nhân';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FitZone – Xem và cập nhật thông tin cá nhân">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | FitZone</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
    <link href="../../assets/css/profile.css" rel="stylesheet">
</head>

<body class="dashboard-page">
<div class="dashboard-layout">

    <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

    <div class="main-area">

        <?php include __DIR__ . '/../../layouts/topbar.php'; ?>

        <main class="dashboard-content">

            <!-- PAGE INTRO -->
            <section class="page-intro">
                <p class="banner-label">ACCOUNT PROFILE</p>
                <h2>Thông tin cá nhân</h2>
                <p>Xem chi tiết tài khoản và cập nhật thông tin liên hệ của bạn tại FitZone.</p>
            </section>

            <!-- FEEDBACK THÔNG BÁO -->
            <div id="profileFeedback" class="profile-feedback" role="status" aria-live="polite"></div>

            <section class="section-block">
                <div class="profile-layout">

                    <!-- CỘT TRÁI: THẺ TÓM TẮT HỒ SƠ -->
                    <aside class="profile-summary-card">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar" id="summaryAvatar">FZ</div>
                        </div>

                        <div class="profile-summary-info">
                            <h3 id="summaryName">Đang tải...</h3>
                            <span class="profile-role-badge" id="summaryRole">-</span>
                            <div style="margin-top: 8px;">
                                <span class="profile-status-badge" id="summaryStatus">Đang hoạt động</span>
                            </div>
                        </div>

                        <div class="profile-summary-divider"></div>

                        <div class="profile-summary-list">
                            <div class="profile-summary-item">
                                <span class="item-label">Mã tài khoản</span>
                                <span class="item-value" id="summaryId">#---</span>
                            </div>
                            <div class="profile-summary-item">
                                <span class="item-label">Email liên hệ</span>
                                <span class="item-value" id="summaryEmail">Đang tải...</span>
                            </div>
                            <div class="profile-summary-item">
                                <span class="item-label">Số điện thoại</span>
                                <span class="item-value" id="summaryPhone">Đang tải...</span>
                            </div>
                            <div class="profile-summary-item">
                                <span class="item-label">Ngày tham gia</span>
                                <span class="item-value" id="summaryCreated">-</span>
                            </div>
                        </div>
                    </aside>

                    <!-- CỘT PHẢI: FORM CHỈNH SỬA THÔNG TIN -->
                    <div class="profile-form-card">
                        <div class="form-heading">
                            <h2>Cập nhật thông tin</h2>
                            <p>Các thông tin có thể chỉnh sửa bao gồm họ tên và số điện thoại.</p>
                        </div>

                        <form id="profileForm" novalidate>
                            <div class="profile-form-grid">

                                <!-- Họ và tên -->
                                <div class="profile-field full-width">
                                    <label for="fullName" class="form-label">
                                        <span>Họ và tên <strong class="text-danger">*</strong></span>
                                    </label>
                                    <input
                                        type="text"
                                        id="fullName"
                                        name="full_name"
                                        class="form-control"
                                        required
                                        maxlength="100"
                                        minlength="2"
                                        placeholder="Nhập họ và tên đầy đủ"
                                        autocomplete="name"
                                    >
                                </div>

                                <!-- Số điện thoại -->
                                <div class="profile-field">
                                    <label for="phone" class="form-label">
                                        <span>Số điện thoại <strong class="text-danger">*</strong></span>
                                    </label>
                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        class="form-control"
                                        required
                                        pattern="[0-9+() .\-]{9,15}"
                                        maxlength="20"
                                        placeholder="Ví dụ: 0901234567"
                                        autocomplete="tel"
                                    >
                                    <span class="form-text">Định dạng số điện thoại từ 9 đến 15 chữ số</span>
                                </div>

                                <!-- Email (Không đổi) -->
                                <div class="profile-field">
                                    <label for="email" class="form-label">
                                        <span>Địa chỉ email</span>
                                        <span class="profile-readonly-badge">Cố định</span>
                                    </label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control"
                                        disabled
                                        readonly
                                        autocomplete="email"
                                    >
                                    <span class="form-text">Dùng làm tên đăng nhập tài khoản</span>
                                </div>

                                <!-- Vai trò tài khoản -->
                                <div class="profile-field">
                                    <label for="roleText" class="form-label">
                                        <span>Vai trò hệ thống</span>
                                        <span class="profile-readonly-badge">Phân quyền</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="roleText"
                                        class="form-control"
                                        disabled
                                        readonly
                                    >
                                </div>

                                <!-- Ngày tạo tài khoản -->
                                <div class="profile-field">
                                    <label for="createdAt" class="form-label">
                                        <span>Ngày kích hoạt</span>
                                        <span class="profile-readonly-badge">Tự động</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="createdAt"
                                        class="form-control"
                                        disabled
                                        readonly
                                    >
                                </div>

                            </div>

                            <!-- Nút thao tác -->
                            <div class="profile-actions">
                                <button type="submit" class="profile-submit-btn" id="saveBtn">
                                    <span>Lưu thay đổi</span>
                                    <span aria-hidden="true">→</span>
                                </button>
                                <button type="button" class="profile-cancel-btn" id="resetBtn">
                                    Khôi phục dữ liệu ban đầu
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </section>

            <!-- FOOTER DÙNG CHUNG -->
            <footer class="dashboard-footer">
                &copy; <?= date('Y') ?> FitZone. All rights reserved.
            </footer>

        </main>

    </div>

</div>

<!-- OVERLAY MOBILE -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- CSRF TOKEN -->
<input type="hidden" id="pageCsrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/dashboard.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const csrfToken = document.getElementById("pageCsrfToken")?.value || "";
    const api       = new FitzoneAPI("../../../backend/api/", csrfToken);

    const feedbackEl    = document.getElementById("profileFeedback");
    const formEl        = document.getElementById("profileForm");
    const saveBtn       = document.getElementById("saveBtn");
    const resetBtn      = document.getElementById("resetBtn");

    const inputName     = document.getElementById("fullName");
    const inputPhone    = document.getElementById("phone");
    const inputEmail    = document.getElementById("email");
    const inputRole     = document.getElementById("roleText");
    const inputCreated  = document.getElementById("createdAt");

    const summaryAvatar = document.getElementById("summaryAvatar");
    const summaryName   = document.getElementById("summaryName");
    const summaryRole   = document.getElementById("summaryRole");
    const summaryStatus = document.getElementById("summaryStatus");
    const summaryId     = document.getElementById("summaryId");
    const summaryEmail  = document.getElementById("summaryEmail");
    const summaryPhone  = document.getElementById("summaryPhone");
    const summaryCreated= document.getElementById("summaryCreated");

    let initialProfile = null;

    const roleNameMap = {
        admin:   "Quản trị viên",
        staff:   "Nhân viên",
        trainer: "Huấn luyện viên",
        member:  "Hội viên"
    };

    function showNotice(text, isError = false) {
        if (!feedbackEl) return;
        feedbackEl.textContent = text;
        feedbackEl.classList.toggle("is-error", isError);
        feedbackEl.classList.add("is-visible");
        feedbackEl.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }

    function hideNotice() {
        if (!feedbackEl) return;
        feedbackEl.textContent = "";
        feedbackEl.classList.remove("is-visible", "is-error");
    }

    function getInitials(name) {
        if (!name) return "FZ";
        const tokens = name.trim().split(/\s+/);
        if (tokens.length >= 2) {
            return (tokens[0][0] + tokens[tokens.length - 1][0]).toUpperCase();
        }
        return name.slice(0, 2).toUpperCase();
    }

    function renderProfile(profile) {
        initialProfile = profile;

        const roleText = roleNameMap[profile.role] || profile.role || "Hội viên";
        const createdDate = profile.created_at ? FitzoneUI.formatDate(profile.created_at.split(" ")[0]) : "-";

        // Cập nhật form
        inputName.value    = profile.full_name || "";
        inputPhone.value   = profile.phone || "";
        inputEmail.value   = profile.email || "";
        inputRole.value    = roleText;
        inputCreated.value = profile.created_at || "";

        // Cập nhật tóm tắt bên trái
        summaryAvatar.textContent  = getInitials(profile.full_name);
        summaryName.textContent    = profile.full_name || "-";
        summaryRole.textContent    = roleText;
        summaryStatus.textContent  = (profile.status === "active") ? "Đang hoạt động" : "Tạm khóa";
        summaryId.textContent      = "#" + (profile.member_id || "---");
        summaryEmail.textContent   = profile.email || "-";
        summaryPhone.textContent   = profile.phone || "-";
        summaryCreated.textContent = createdDate;

        // Cập nhật tên hiển thị ở Topbar (nếu có)
        const topbarNameEl = document.querySelector(".topbar .user-text strong");
        if (topbarNameEl && profile.full_name) {
            topbarNameEl.textContent = profile.full_name;
        }
    }

    async function fetchProfile() {
        try {
            hideNotice();
            const data = await api.get("profile/me.php");
            renderProfile(data);
        } catch (error) {
            showNotice(error.message || "Không thể tải thông tin hồ sơ.", true);
        }
    }

    formEl.addEventListener("submit", async (e) => {
        e.preventDefault();

        if (!formEl.checkValidity()) {
            formEl.reportValidity();
            return;
        }

        const fullName = inputName.value.trim();
        const phone    = inputPhone.value.trim();

        if (!fullName || !phone) {
            showNotice("Vui lòng điền đầy đủ họ tên và số điện thoại.", true);
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span>Đang lưu...</span>`;
        hideNotice();

        try {
            const res = await api.post("profile/update.php", {
                full_name: fullName,
                phone:     phone
            });

            showNotice(res?.message || "Cập nhật thông tin cá nhân thành công!", false);
            await fetchProfile();
        } catch (error) {
            showNotice(error.message || "Cập nhật thông tin thất bại. Vui lòng thử lại.", true);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<span>Lưu thay đổi</span> <span aria-hidden="true">→</span>`;
        }
    });

    resetBtn.addEventListener("click", () => {
        if (initialProfile) {
            renderProfile(initialProfile);
            showNotice("Đã khôi phục dữ liệu ban đầu.", false);
        }
    });

    fetchProfile();
});
</script>

</body>
</html>
