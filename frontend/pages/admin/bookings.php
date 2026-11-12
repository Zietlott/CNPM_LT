<?php

require_once __DIR__ . '/../../../backend/helpers/auth.php';

if (!isAdmin() && !isStaff()) {
    header('Location: ../auth/login.php');
    exit;
}

$isAdminUser = isAdmin();
$csrfToken = getCsrfToken();
$fullName = $_SESSION['full_name'] ?? ($isAdminUser ? 'Quản trị viên' : 'Nhân viên');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý booking | FitZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
    <link href="../../assets/css/booking.css" rel="stylesheet">
</head>
<body class="dashboard-page">
<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <a href="bookings.php" class="brand">
            <span class="brand-mark">F</span><span>FITZONE</span>
        </a>
        <p class="menu-label"><?= $isAdminUser ? 'QUẢN TRỊ' : 'NHÂN VIÊN' ?></p>
        <nav class="sidebar-nav">
            <a href="bookings.php" class="nav-item active">
                <span class="nav-icon">▦</span><span>Quản lý booking</span>
            </a>
        </nav>
        <div class="sidebar-bottom">
            <a href="../auth/logout.php" class="nav-item logout-link">
                <span class="nav-icon">↪</span><span>Đăng xuất</span>
            </a>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle"
                    aria-label="Mở menu" aria-controls="sidebar" aria-expanded="false">☰</button>
            <div class="topbar-title">
                <span class="topbar-label"><?= $isAdminUser ? 'ADMIN AREA' : 'STAFF AREA' ?></span>
                <h1>Quản lý booking</h1>
            </div>
            <div class="user-info">
                <div class="user-avatar"><?= $isAdminUser ? 'AD' : 'ST' ?></div>
                <div class="user-text">
                    <strong><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= $isAdminUser ? 'Quản trị viên' : 'Nhân viên' ?></span>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <section class="page-intro">
                <p class="banner-label">BOOKING OPERATIONS</p>
                <h2>Booking hội viên</h2>
                <p>Admin có thể xác nhận hoặc hủy thủ công. Staff chỉ thao tác trong phạm vi quyền Admin cấp.</p>
            </section>

            <p id="adminBookingFeedback" class="booking-feedback"
               role="status" aria-live="polite"></p>

            <?php if ($isAdminUser): ?>
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
                © <?= date('Y') ?> FitZone. All rights reserved.
            </footer>
        </main>
    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<input type="hidden" id="bookingCsrfToken"
       value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" id="isAdminUser" value="<?= $isAdminUser ? '1' : '0' ?>">
<script src="../../assets/js/dashboard.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const apiBase = "../../../backend/api/booking/";
    const csrfToken = document.getElementById("bookingCsrfToken").value;
    const isAdmin = document.getElementById("isAdminUser").value === "1";
    const feedback = document.getElementById("adminBookingFeedback");
    const bookingsElement = document.getElementById("adminBookings");
    const trainerSelect = document.getElementById("trainerAccountTrainer");
    const staffList = document.getElementById("staffPermissions");
    const statusFilter = document.getElementById("bookingStatusFilter");

    async function requestJson(path, options = {}) {
        const response = await fetch(apiBase + path, options);
        let payload;
        try {
            payload = await response.json();
        } catch (error) {
            throw new Error("Máy chủ trả về dữ liệu không hợp lệ.");
        }
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || "Không thể xử lý yêu cầu.");
        }
        return payload.data;
    }

    function escapeHtml(value) {
        return String(value ?? "").replace(/[&<>"']/g, function (character) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            }[character];
        });
    }

    function showFeedback(message, isError = false) {
        feedback.textContent = message;
        feedback.classList.toggle("is-error", isError);
        feedback.classList.add("is-visible");
    }

    function formatDate(value) {
        return new Intl.DateTimeFormat("vi-VN").format(
            new Date(value + "T00:00:00")
        );
    }

    async function loadBookings() {
        const params = new URLSearchParams();
        if (isAdmin && statusFilter && statusFilter.value) {
            params.set("status", statusFilter.value);
        }
        const suffix = params.toString() ? "?" + params.toString() : "";
        const bookings = await requestJson("admin-bookings.php" + suffix);
        if (bookings.length === 0) {
            bookingsElement.innerHTML =
                '<p class="booking-list-empty">Không có booking phù hợp.</p>';
            return;
        }

        bookingsElement.innerHTML = bookings.map(function (booking) {
            const actions = [];
            if (isAdmin && booking.status === "pending") {
                actions.push(
                    '<button type="button" class="booking-management-button" data-action="confirm" data-booking-id="' +
                    escapeHtml(booking.booking_id) + '">Xác nhận</button>'
                );
            } else if (!isAdmin && booking.status === "pending"
                       && Number(booking.can_confirm) === 1) {
                actions.push(
                    '<button type="button" class="booking-management-button" data-action="confirm" data-booking-id="' +
                    escapeHtml(booking.booking_id) + '">Xác nhận</button>'
                );
            }

            const mayCancel = isAdmin
                ? ["pending", "confirmed"].includes(booking.status)
                : Number(booking.can_cancel) === 1;
            if (mayCancel && ["pending", "confirmed"].includes(booking.status)) {
                actions.push(
                    '<button type="button" class="booking-cancel-button" data-action="cancel" data-booking-id="' +
                    escapeHtml(booking.booking_id) + '">Hủy</button>'
                );
            }

            const statusLabels = {
                pending: "Chờ xác nhận",
                confirmed: "Đã xác nhận",
                rejected: "Đã từ chối",
                cancelled: "Đã hủy",
                completed: "Đã hoàn thành"
            };
            const changeDetails = booking.pending_change_request_id
                ? '<p class="booking-change-pending">Đang chờ PT duyệt đổi lịch sang ' +
                    escapeHtml(formatDate(booking.requested_work_date)) + " · " +
                    escapeHtml(booking.requested_start_time.slice(0, 5)) + " - " +
                    escapeHtml(booking.requested_end_time.slice(0, 5)) + "</p>"
                : "";

            return '<article class="booking-list-item">' +
                '<div class="booking-list-item-main">' +
                    '<div class="booking-list-item-heading">' +
                        "<h3>" + escapeHtml(booking.member_name) + " · " +
                            escapeHtml(booking.trainer_name) + "</h3>" +
                        '<span class="booking-status booking-status-' +
                            escapeHtml(booking.status) + '">' +
                            escapeHtml(statusLabels[booking.status] || "Không xác định") + "</span>" +
                    "</div>" +
                    '<p class="booking-slot-detail">' +
                        escapeHtml(formatDate(booking.work_date)) + " · " +
                        escapeHtml(booking.start_time.slice(0, 5)) + " - " +
                        escapeHtml(booking.end_time.slice(0, 5)) +
                    "</p>" +
                    (booking.note
                        ? '<p class="booking-member-note"><strong>Ghi chú:</strong> ' +
                            escapeHtml(booking.note) + "</p>"
                        : "") +
                    (booking.cancel_reason
                        ? '<p class="booking-cancel-reason"><strong>Lý do:</strong> ' +
                            escapeHtml(booking.cancel_reason) + "</p>"
                        : "") +
                    changeDetails +
                "</div>" +
                '<div class="booking-actions">' + actions.join("") + "</div>" +
            "</article>";
        }).join("");
    }

    async function loadAccessData() {
        if (!isAdmin) return;
        const access = await requestJson("admin-access.php");
        const unlinkedTrainers = access.trainers.filter(function (trainer) {
            return trainer.member_id === null && Number(trainer.is_active) === 1;
        });
        trainerSelect.innerHTML = '<option value="">-- Chọn PT chưa có tài khoản --</option>';
        unlinkedTrainers.forEach(function (trainer) {
            const option = document.createElement("option");
            option.value = trainer.trainer_id;
            option.textContent = trainer.full_name;
            trainerSelect.appendChild(option);
        });
        if (unlinkedTrainers.length === 0) {
            trainerSelect.options[0].textContent = "Tất cả hồ sơ PT đã có tài khoản";
        }

        if (access.staff.length === 0) {
            staffList.innerHTML = '<p class="booking-list-empty">Chưa có tài khoản Staff.</p>';
            return;
        }
        staffList.innerHTML = access.staff.map(function (staff) {
            return '<article class="booking-list-item">' +
                '<div class="booking-list-item-main">' +
                    '<div class="booking-list-item-heading"><h3>' +
                        escapeHtml(staff.full_name) + '</h3></div>' +
                    '<p class="booking-slot-detail">' + escapeHtml(staff.email) +
                        ' · Xác nhận: ' + (Number(staff.can_confirm) === 1 ? "Có" : "Không") +
                        ' · Hủy: ' + (Number(staff.can_cancel) === 1 ? "Có" : "Không") +
                    "</p>" +
                "</div>" +
                '<div class="booking-actions">' +
                    '<label class="booking-inline-permission"><input type="checkbox" data-staff-confirm="' +
                        escapeHtml(staff.member_id) + '"' +
                        (Number(staff.can_confirm) === 1 ? " checked" : "") +
                        '> Xác nhận</label>' +
                    '<label class="booking-inline-permission"><input type="checkbox" data-staff-cancel="' +
                        escapeHtml(staff.member_id) + '"' +
                        (Number(staff.can_cancel) === 1 ? " checked" : "") +
                        '> Hủy</label>' +
                    '<button type="button" class="booking-management-secondary" data-save-staff="' +
                        escapeHtml(staff.member_id) + '">Lưu quyền</button>' +
                "</div>" +
            "</article>";
        }).join("");
    }

    bookingsElement.addEventListener("click", async function (event) {
        const button = event.target.closest("[data-action]");
        if (!button) return;
        const action = button.dataset.action;
        const reason = window.prompt(
            action === "confirm"
                ? "Nhập lý do xác nhận booking:"
                : "Nhập lý do hủy booking:"
        ) || "";
        if (!reason.trim()) return;

        try {
            await requestJson("admin-bookings.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify({
                    booking_id: button.dataset.bookingId,
                    action: action,
                    reason: reason
                })
            });
            await loadBookings();
            showFeedback("Đã cập nhật booking.");
        } catch (error) {
            showFeedback(error.message, true);
        }
    });

    if (isAdmin) {
        statusFilter.addEventListener("change", function () {
            loadBookings().catch(function (error) {
                showFeedback(error.message, true);
            });
        });

        document.getElementById("trainerAccountForm").addEventListener("submit", async function (event) {
            event.preventDefault();
            const form = event.currentTarget;
            if (!form.reportValidity()) return;
            try {
                await requestJson("admin-access.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": csrfToken
                    },
                    body: JSON.stringify({
                        action: "create_trainer",
                        trainer_id: trainerSelect.value,
                        email: document.getElementById("trainerAccountEmail").value,
                        phone: document.getElementById("trainerAccountPhone").value,
                        password: document.getElementById("trainerAccountPassword").value
                    })
                });
                form.reset();
                await loadAccessData();
                showFeedback("Đã tạo tài khoản PT.");
            } catch (error) {
                showFeedback(error.message, true);
            }
        });

        document.getElementById("staffAccountForm").addEventListener("submit", async function (event) {
            event.preventDefault();
            const form = event.currentTarget;
            if (!form.reportValidity()) return;
            try {
                await requestJson("admin-access.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": csrfToken
                    },
                    body: JSON.stringify({
                        action: "create_staff",
                        full_name: document.getElementById("staffFullName").value,
                        email: document.getElementById("staffEmail").value,
                        phone: document.getElementById("staffPhone").value,
                        password: document.getElementById("staffPassword").value,
                        can_confirm: document.getElementById("staffCanConfirm").checked,
                        can_cancel: document.getElementById("staffCanCancel").checked
                    })
                });
                form.reset();
                await loadAccessData();
                showFeedback("Đã tạo tài khoản Staff và lưu quyền.");
            } catch (error) {
                showFeedback(error.message, true);
            }
        });

        staffList.addEventListener("click", async function (event) {
            const button = event.target.closest("[data-save-staff]");
            if (!button) return;
            const staffId = button.dataset.saveStaff;
            const confirmInput = staffList.querySelector(
                '[data-staff-confirm="' + CSS.escape(staffId) + '"]'
            );
            const cancelInput = staffList.querySelector(
                '[data-staff-cancel="' + CSS.escape(staffId) + '"]'
            );
            try {
                await requestJson("admin-access.php", {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": csrfToken
                    },
                    body: JSON.stringify({
                        action: "update_staff_permissions",
                        staff_id: staffId,
                        can_confirm: confirmInput.checked,
                        can_cancel: cancelInput.checked
                    })
                });
                await loadAccessData();
                showFeedback("Đã cập nhật quyền Staff.");
            } catch (error) {
                showFeedback(error.message, true);
            }
        });
    }

    Promise.all([loadBookings(), loadAccessData()]).catch(function (error) {
        showFeedback(error.message, true);
    });
});
</script>
</body>
</html>
