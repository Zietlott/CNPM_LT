<?php

require_once __DIR__ . '/../../../backend/helpers/auth.php';

/* Bảo vệ trang - chỉ admin và staff */
if (!isAdmin() && !isStaff()) {
    header('Location: ../auth/login.php');
    exit;
}

$isAdminUser = isAdmin();
$sidebarRole = $isAdminUser ? 'admin' : 'staff';
$csrfToken   = getCsrfToken();
$fullName    = $_SESSION['full_name'] ?? ($isAdminUser ? 'Quản trị viên' : 'Nhân viên');

/* Biến layout dùng chung */
$pageTitle   = "Quản lý booking";
$activeNav   = 'bookings';
$topbarLabel = $isAdminUser ? 'ADMIN AREA' : 'STAFF AREA';
$topbarTitle = 'Quản lý booking';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | FitZone</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
    <link href="../../assets/css/booking.css" rel="stylesheet">
</head>

<body class="dashboard-page">
<div class="dashboard-layout">

    <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

    <div class="main-area">

        <?php include __DIR__ . '/../../layouts/topbar.php'; ?>

        <main class="dashboard-content">

            <section class="page-intro">
                <p class="banner-label">BOOKING OPERATIONS</p>
                <h2>Booking hội viên</h2>
                <p>
                    <?php if ($isAdminUser): ?>
                        Admin có thể xác nhận hoặc hủy thủ công, tạo tài khoản PT và Staff.
                    <?php else: ?>
                        Staff chỉ thao tác trong phạm vi quyền Admin cấp.
                    <?php endif; ?>
                </p>
            </section>

            <p id="adminBookingFeedback" class="booking-feedback" role="status" aria-live="polite"></p>

            <!-- Chỉ Admin mới thấy khu vực tạo tài khoản -->
            <?php if ($isAdminUser): ?>

                <!-- TẠO TRAINER ACCOUNT -->
                <section class="section-block booking-management-section">
                    <div class="booking-management-heading">
                        <div>
                            <p class="banner-label">TRAINER ACCESS</p>
                            <h2>Tạo tài khoản đăng nhập cho PT</h2>
                        </div>
                    </div>
                    <p class="booking-management-description">
                        Tài khoản được liên kết với hồ sơ PT để PT chỉ xem lịch và booking của chính mình.
                    </p>
                    <form id="trainerAccountForm" class="booking-access-form">
                        <div>
                            <label for="trainerAccountTrainer" class="form-label">Hồ sơ PT</label>
                            <select id="trainerAccountTrainer" class="form-select" required>
                                <option value="">Đang tải hồ sơ PT...</option>
                            </select>
                        </div>
                        <div>
                            <label for="trainerAccountEmail" class="form-label">Email đăng nhập</label>
                            <input type="email" id="trainerAccountEmail" class="form-control" required maxlength="150">
                        </div>
                        <div>
                            <label for="trainerAccountPhone" class="form-label">Số điện thoại</label>
                            <input type="text" id="trainerAccountPhone" class="form-control" required maxlength="20">
                        </div>
                        <div>
                            <label for="trainerAccountPassword" class="form-label">Mật khẩu (ít nhất 8 ký tự)</label>
                            <input type="password" id="trainerAccountPassword" class="form-control" required minlength="8">
                        </div>
                        <button type="submit" class="booking-management-button">Tạo tài khoản PT</button>
                    </form>
                </section>

                <!-- TẠO STAFF ACCOUNT -->
                <section class="section-block booking-management-section">
                    <div class="booking-management-heading">
                        <div>
                            <p class="banner-label">STAFF ACCESS</p>
                            <h2>Tạo Staff và cấp quyền booking</h2>
                        </div>
                    </div>
                    <p class="booking-management-description">
                        Không cấp quyền thao tác mặc định. Chọn riêng quyền xác nhận và hủy khi tạo tài khoản.
                    </p>
                    <form id="staffAccountForm" class="booking-access-form">
                        <div>
                            <label for="staffFullName" class="form-label">Họ tên</label>
                            <input type="text" id="staffFullName" class="form-control" required maxlength="100">
                        </div>
                        <div>
                            <label for="staffEmail" class="form-label">Email</label>
                            <input type="email" id="staffEmail" class="form-control" required maxlength="150">
                        </div>
                        <div>
                            <label for="staffPhone" class="form-label">Số điện thoại</label>
                            <input type="text" id="staffPhone" class="form-control" required maxlength="20">
                        </div>
                        <div>
                            <label for="staffPassword" class="form-label">Mật khẩu (ít nhất 8 ký tự)</label>
                            <input type="password" id="staffPassword" class="form-control" required minlength="8">
                        </div>
                        <label class="booking-permission-check">
                            <input type="checkbox" id="staffCanConfirm">
                            <span>Được xác nhận booking</span>
                        </label>
                        <label class="booking-permission-check">
                            <input type="checkbox" id="staffCanCancel">
                            <span>Được hủy booking</span>
                        </label>
                        <button type="submit" class="booking-management-button">Tạo tài khoản Staff</button>
                    </form>
                    <div id="staffPermissions" class="booking-list booking-staff-list">
                        <p class="booking-list-empty">Đang tải danh sách Staff...</p>
                    </div>
                </section>

            <?php endif; ?>

            <!-- DANH SÁCH BOOKING (tất cả admin & staff đều thấy) -->
            <section class="section-block booking-management-section">
                <div class="booking-management-heading booking-admin-filters">
                    <div>
                        <p class="banner-label">BOOKING OVERVIEW</p>
                        <h2>Danh sách booking</h2>
                    </div>
                    <?php if ($isAdminUser): ?>
                        <label class="booking-filter-label" for="bookingStatusFilter">
                            Trạng thái
                            <select id="bookingStatusFilter" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="pending">Chờ xác nhận</option>
                                <option value="confirmed">Đã xác nhận</option>
                                <option value="rejected">Đã từ chối</option>
                                <option value="cancelled">Đã hủy</option>
                                <option value="completed">Đã hoàn thành</option>
                            </select>
                        </label>
                    <?php endif; ?>
                </div>
                <div id="adminBookings" class="booking-list">
                    <p class="booking-list-empty">Đang tải booking...</p>
                </div>
            </section>

            <footer class="dashboard-footer">
                &copy; <?= date('Y') ?> FitZone. All rights reserved.
            </footer>

        </main>

    </div>

</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Data cho JS -->
<input type="hidden" id="bookingCsrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" id="isAdminUser"      value="<?= $isAdminUser ? '1' : '0' ?>">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/dashboard.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const csrfToken     = document.getElementById("bookingCsrfToken").value;
    const isAdmin       = document.getElementById("isAdminUser").value === "1";
    const api           = new FitzoneAPI("../../../backend/api/", csrfToken);
    const feedback      = document.getElementById("adminBookingFeedback");
    const bookingsEl    = document.getElementById("adminBookings");
    const trainerSelect = document.getElementById("trainerAccountTrainer");
    const staffList     = document.getElementById("staffPermissions");
    const statusFilter  = document.getElementById("bookingStatusFilter");

    function showFeedback(msg, isError = false) {
        FitzoneUI.showFeedback(feedback, msg, isError ? "error" : "success");
    }

    /* ---- Load bookings ---- */
    async function loadBookings() {
        const params = {};
        if (isAdmin && statusFilter && statusFilter.value) {
            params.status = statusFilter.value;
        }
        const bookings = await api.getQuery("booking/admin-bookings.php", params);

        if (!bookings || bookings.length === 0) {
            bookingsEl.innerHTML = FitzoneUI.empty("Không có booking phù hợp.");
            return;
        }

        bookingsEl.innerHTML = bookings.map(function (b) {
            const actions = [];
            const mayConfirm = isAdmin
                ? b.status === "pending"
                : b.status === "pending" && Number(b.can_confirm) === 1;
            if (mayConfirm) {
                actions.push(`<button type="button" class="booking-management-button" data-action="confirm" data-booking-id="${FitzoneUI.escape(b.booking_id)}">Xác nhận</button>`);
            }
            const mayCancel = isAdmin
                ? ["pending","confirmed"].includes(b.status)
                : Number(b.can_cancel) === 1 && ["pending","confirmed"].includes(b.status);
            if (mayCancel) {
                actions.push(`<button type="button" class="booking-cancel-button" data-action="cancel" data-booking-id="${FitzoneUI.escape(b.booking_id)}">Hủy</button>`);
            }

            const changeDetails = b.pending_change_request_id
                ? `<p class="booking-change-pending">Đang chờ PT duyệt đổi lịch sang ${FitzoneUI.escape(FitzoneUI.formatDate(b.requested_work_date))} · ${FitzoneUI.escape((b.requested_start_time||"").slice(0,5))} - ${FitzoneUI.escape((b.requested_end_time||"").slice(0,5))}</p>`
                : "";

            return `<article class="booking-list-item">
                <div class="booking-list-item-main">
                    <div class="booking-list-item-heading">
                        <h3>${FitzoneUI.escape(b.member_name)} · ${FitzoneUI.escape(b.trainer_name)}</h3>
                        ${FitzoneUI.statusBadge(b.status, FitzoneUI.bookingStatusLabel(b.status))}
                    </div>
                    <p class="booking-slot-detail">${FitzoneUI.escape(FitzoneUI.formatDate(b.work_date))} · ${FitzoneUI.escape((b.start_time||"").slice(0,5))} - ${FitzoneUI.escape((b.end_time||"").slice(0,5))}</p>
                    ${b.note ? `<p class="booking-member-note"><strong>Ghi chú:</strong> ${FitzoneUI.escape(b.note)}</p>` : ""}
                    ${b.cancel_reason ? `<p class="booking-cancel-reason"><strong>Lý do:</strong> ${FitzoneUI.escape(b.cancel_reason)}</p>` : ""}
                    ${changeDetails}
                </div>
                <div class="booking-actions">${actions.join("")}</div>
            </article>`;
        }).join("");
    }

    /* ---- Load access data (admin only) ---- */
    async function loadAccessData() {
        if (!isAdmin) return;
        const access = await api.get("booking/admin-access.php");
        const unlinked = (access.trainers || []).filter(t => t.member_id === null && Number(t.is_active) === 1);
        trainerSelect.innerHTML = '<option value="">-- Chọn PT chưa có tài khoản --</option>';
        unlinked.forEach(function (t) {
            const opt = document.createElement("option");
            opt.value = t.trainer_id;
            opt.textContent = t.full_name;
            trainerSelect.appendChild(opt);
        });
        if (unlinked.length === 0) {
            trainerSelect.options[0].textContent = "Tất cả hồ sơ PT đã có tài khoản";
        }

        if (!access.staff || access.staff.length === 0) {
            staffList.innerHTML = FitzoneUI.empty("Chưa có tài khoản Staff.");
            return;
        }
        staffList.innerHTML = access.staff.map(function (s) {
            return `<article class="booking-list-item">
                <div class="booking-list-item-main">
                    <div class="booking-list-item-heading"><h3>${FitzoneUI.escape(s.full_name)}</h3></div>
                    <p class="booking-slot-detail">${FitzoneUI.escape(s.email)} · Xác nhận: ${Number(s.can_confirm) ? "Có" : "Không"} · Hủy: ${Number(s.can_cancel) ? "Có" : "Không"}</p>
                </div>
                <div class="booking-actions">
                    <label class="booking-inline-permission"><input type="checkbox" data-staff-confirm="${FitzoneUI.escape(s.member_id)}"${Number(s.can_confirm) ? " checked" : ""}> Xác nhận</label>
                    <label class="booking-inline-permission"><input type="checkbox" data-staff-cancel="${FitzoneUI.escape(s.member_id)}"${Number(s.can_cancel) ? " checked" : ""}> Hủy</label>
                    <button type="button" class="booking-management-secondary" data-save-staff="${FitzoneUI.escape(s.member_id)}">Lưu quyền</button>
                </div>
            </article>`;
        }).join("");
    }

    /* ---- Events: Confirm / Cancel booking ---- */
    bookingsEl.addEventListener("click", async function (event) {
        const btn = event.target.closest("[data-action]");
        if (!btn) return;
        const action = btn.dataset.action;
        const reason = window.prompt(action === "confirm" ? "Nhập lý do xác nhận:" : "Nhập lý do hủy:") || "";
        if (!reason.trim()) return;
        try {
            await api.post("booking/admin-bookings.php", { booking_id: btn.dataset.bookingId, action, reason });
            await loadBookings();
            showFeedback("Đã cập nhật booking.");
        } catch (err) { showFeedback(err.message, true); }
    });

    /* ---- Events: Admin only ---- */
    if (isAdmin) {
        statusFilter && statusFilter.addEventListener("change", () => loadBookings().catch(e => showFeedback(e.message, true)));

        document.getElementById("trainerAccountForm").addEventListener("submit", async function (e) {
            e.preventDefault();
            if (!this.reportValidity()) return;
            try {
                await api.post("booking/admin-access.php", {
                    action:     "create_trainer",
                    trainer_id: trainerSelect.value,
                    email:      document.getElementById("trainerAccountEmail").value,
                    phone:      document.getElementById("trainerAccountPhone").value,
                    password:   document.getElementById("trainerAccountPassword").value,
                });
                this.reset(); await loadAccessData(); showFeedback("Đã tạo tài khoản PT.");
            } catch (err) { showFeedback(err.message, true); }
        });

        document.getElementById("staffAccountForm").addEventListener("submit", async function (e) {
            e.preventDefault();
            if (!this.reportValidity()) return;
            try {
                await api.post("booking/admin-access.php", {
                    action:      "create_staff",
                    full_name:   document.getElementById("staffFullName").value,
                    email:       document.getElementById("staffEmail").value,
                    phone:       document.getElementById("staffPhone").value,
                    password:    document.getElementById("staffPassword").value,
                    can_confirm: document.getElementById("staffCanConfirm").checked,
                    can_cancel:  document.getElementById("staffCanCancel").checked,
                });
                this.reset(); await loadAccessData(); showFeedback("Đã tạo tài khoản Staff.");
            } catch (err) { showFeedback(err.message, true); }
        });

        staffList.addEventListener("click", async function (event) {
            const btn = event.target.closest("[data-save-staff]");
            if (!btn) return;
            const staffId = btn.dataset.saveStaff;
            const confirmInput = staffList.querySelector(`[data-staff-confirm="${CSS.escape(staffId)}"]`);
            const cancelInput  = staffList.querySelector(`[data-staff-cancel="${CSS.escape(staffId)}"]`);
            try {
                await api.put("booking/admin-access.php", {
                    action:      "update_staff_permissions",
                    staff_id:    staffId,
                    can_confirm: confirmInput.checked,
                    can_cancel:  cancelInput.checked,
                });
                await loadAccessData(); showFeedback("Đã cập nhật quyền Staff.");
            } catch (err) { showFeedback(err.message, true); }
        });
    }

    Promise.all([loadBookings(), loadAccessData()]).catch(e => showFeedback(e.message, true));
});
</script>
</body>
</html>
