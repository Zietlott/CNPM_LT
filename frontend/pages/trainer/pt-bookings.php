<?php

require_once __DIR__ . '/../../../backend/helpers/auth.php';

if (!isTrainer()) {
    header('Location: ../auth/login.php');
    exit;
}

$csrfToken = getCsrfToken();
$fullName = $_SESSION['full_name'] ?? 'Huấn luyện viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý lịch PT | FitZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
    <link href="../../assets/css/booking.css" rel="stylesheet">
</head>
<body class="dashboard-page">
<div class="dashboard-layout">
    <aside class="sidebar" id="sidebar">
        <a href="pt-bookings.php" class="brand">
            <span class="brand-mark">F</span><span>FITZONE</span>
        </a>
        <p class="menu-label">HUẤN LUYỆN VIÊN</p>
        <nav class="sidebar-nav">
            <a href="pt-bookings.php" class="nav-item active">
                <span class="nav-icon">▦</span><span>Booking & lịch làm việc</span>
            </a>
        </nav>
        <div class="sidebar-bottom">
            <form action="../auth/logout.php" method="POST" style="margin: 0;">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="nav-item logout-link"
                        style="width: 100%; border: none; background: transparent; text-align: left; cursor: pointer; font: inherit;">
                    <span class="nav-icon">↪</span><span>Đăng xuất</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle"
                    aria-label="Mở menu" aria-controls="sidebar" aria-expanded="false">☰</button>
            <div class="topbar-title">
                <span class="topbar-label">TRAINER AREA</span>
                <h1>Booking & lịch làm việc</h1>
            </div>
            <div class="user-info">
                <div class="user-avatar">PT</div>
                <div class="user-text">
                    <strong><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span>Huấn luyện viên</span>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <section class="page-intro">
                <p class="banner-label">PERSONAL TRAINER WORKSPACE</p>
                <h2>Quản lý booking và lịch làm việc</h2>
                <p>Xác nhận yêu cầu, xử lý đổi lịch, cập nhật kết quả buổi tập và quản lý khung giờ của bạn.</p>
            </section>

            <p id="trainerFeedback" class="booking-feedback" role="status" aria-live="polite"></p>

            <section class="section-block booking-management-section">
                <div class="booking-management-heading">
                    <div>
                        <p class="banner-label">WORKING HOURS</p>
                        <h2>Lịch làm việc của tôi</h2>
                    </div>
                </div>
                <form id="scheduleForm" class="trainer-schedule-form">
                    <input type="hidden" id="scheduleId">
                    <div>
                        <label for="scheduleDate" class="form-label">Ngày</label>
                        <input type="date" id="scheduleDate" class="form-control" required>
                    </div>
                    <div>
                        <label for="scheduleStart" class="form-label">Bắt đầu</label>
                        <input type="time" id="scheduleStart" class="form-control" required>
                    </div>
                    <div>
                        <label for="scheduleEnd" class="form-label">Kết thúc</label>
                        <input type="time" id="scheduleEnd" class="form-control" required>
                    </div>
                    <div class="trainer-schedule-form-actions">
                        <button type="submit" class="booking-management-button" id="scheduleSubmit">
                            Thêm khung giờ
                        </button>
                        <button type="button" class="booking-management-secondary"
                                id="scheduleReset" hidden>Hủy sửa</button>
                    </div>
                </form>
                <div id="trainerSchedules" class="booking-list">
                    <p class="booking-list-empty">Đang tải lịch làm việc...</p>
                </div>
            </section>

            <section class="section-block booking-management-section">
                <div class="booking-management-heading">
                    <div>
                        <p class="banner-label">MY BOOKINGS</p>
                        <h2>Booking hội viên</h2>
                    </div>
                </div>
                <div id="trainerBookings" class="booking-list">
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
<script src="../../assets/js/dashboard.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const apiBase = "../../../backend/api/booking/";
    const csrfToken = document.getElementById("bookingCsrfToken").value;
    const feedback = document.getElementById("trainerFeedback");
    const schedulesElement = document.getElementById("trainerSchedules");
    const bookingsElement = document.getElementById("trainerBookings");
    const scheduleForm = document.getElementById("scheduleForm");
    const scheduleIdInput = document.getElementById("scheduleId");
    const scheduleDate = document.getElementById("scheduleDate");
    const scheduleStart = document.getElementById("scheduleStart");
    const scheduleEnd = document.getElementById("scheduleEnd");
    const scheduleSubmit = document.getElementById("scheduleSubmit");
    const scheduleReset = document.getElementById("scheduleReset");
    const labels = {
        pending: "Chờ xác nhận",
        confirmed: "Đã xác nhận",
        rejected: "Đã từ chối",
        cancelled: "Đã hủy",
        completed: "Đã hoàn thành"
    };

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

    function resetScheduleForm() {
        scheduleForm.reset();
        scheduleIdInput.value = "";
        scheduleSubmit.textContent = "Thêm khung giờ";
        scheduleReset.hidden = true;
    }

    async function loadSchedules() {
        const schedules = await requestJson("trainer-schedules.php");
        if (schedules.length === 0) {
            schedulesElement.innerHTML =
                '<p class="booking-list-empty">Bạn chưa tạo khung giờ làm việc.</p>';
            return;
        }

        schedulesElement.innerHTML = schedules.map(function (slot) {
            const active = slot.status === "available";
            const canEdit = active && Number(slot.has_booking) === 0;
            const editButton = canEdit
                ? '<button type="button" class="booking-management-secondary" data-edit-schedule="' +
                    escapeHtml(slot.schedule_id) + '" data-date="' +
                    escapeHtml(slot.work_date) + '" data-start="' +
                    escapeHtml(slot.start_time.slice(0, 5)) + '" data-end="' +
                    escapeHtml(slot.end_time.slice(0, 5)) + '">Sửa</button>'
                : "";
            const deactivateButton = canEdit
                ? '<button type="button" class="booking-cancel-button" data-deactivate-schedule="' +
                    escapeHtml(slot.schedule_id) + '">Khóa lịch</button>'
                : "";
            const status = active ? "Đang mở" : "Đã khóa";

            return '<article class="booking-list-item">' +
                '<div class="booking-list-item-main">' +
                    '<div class="booking-list-item-heading">' +
                        "<h3>" + escapeHtml(formatDate(slot.work_date)) + "</h3>" +
                        '<span class="booking-status booking-status-' +
                            (active ? "confirmed" : "cancelled") + '">' +
                            status + "</span>" +
                    "</div>" +
                    '<p class="booking-slot-detail">' +
                        escapeHtml(slot.start_time.slice(0, 5)) + " - " +
                        escapeHtml(slot.end_time.slice(0, 5)) +
                        (Number(slot.has_booking) === 1 ? " · Đã có booking, không thể sửa/xóa" : "") +
                    "</p>" +
                "</div>" +
                '<div class="booking-actions">' + editButton + deactivateButton + "</div>" +
            "</article>";
        }).join("");
    }

    async function loadBookings() {
        const bookings = await requestJson("trainer-bookings.php");
        if (bookings.length === 0) {
            bookingsElement.innerHTML =
                '<p class="booking-list-empty">Chưa có hội viên đặt lịch với bạn.</p>';
            return;
        }

        bookingsElement.innerHTML = bookings.map(function (booking) {
            const actions = [];
            if (booking.status === "pending") {
                actions.push(
                    '<button type="button" class="booking-management-button" data-action="confirm" data-booking-id="' +
                    escapeHtml(booking.booking_id) + '">Xác nhận</button>'
                );
                actions.push(
                    '<button type="button" class="booking-cancel-button" data-action="reject" data-booking-id="' +
                    escapeHtml(booking.booking_id) + '">Từ chối</button>'
                );
            }
            if (booking.status === "confirmed") {
                actions.push(
                    '<button type="button" class="booking-management-button" data-action="complete" data-booking-id="' +
                    escapeHtml(booking.booking_id) + '">Đánh dấu hoàn thành</button>'
                );
            }
            if (booking.pending_change_request_id) {
                actions.push(
                    '<button type="button" class="booking-management-button" data-change-action="approve" data-booking-id="' +
                    escapeHtml(booking.booking_id) + '" data-request-id="' +
                    escapeHtml(booking.pending_change_request_id) + '">Duyệt đổi lịch</button>'
                );
                actions.push(
                    '<button type="button" class="booking-cancel-button" data-change-action="reject" data-booking-id="' +
                    escapeHtml(booking.booking_id) + '" data-request-id="' +
                    escapeHtml(booking.pending_change_request_id) + '">Từ chối đổi lịch</button>'
                );
            }

            const changeDetails = booking.pending_change_request_id
                ? '<p class="booking-change-pending">Đề nghị đổi sang ' +
                    escapeHtml(formatDate(booking.requested_work_date)) + " · " +
                    escapeHtml(booking.requested_start_time.slice(0, 5)) + " - " +
                    escapeHtml(booking.requested_end_time.slice(0, 5)) +
                    '<br>Lý do: ' + escapeHtml(booking.change_request_reason) + "</p>"
                : "";

            return '<article class="booking-list-item">' +
                '<div class="booking-list-item-main">' +
                    '<div class="booking-list-item-heading">' +
                        "<h3>" + escapeHtml(booking.member_name) + "</h3>" +
                        '<span class="booking-status booking-status-' +
                            escapeHtml(booking.status) + '">' +
                            escapeHtml(labels[booking.status] || "Không xác định") + "</span>" +
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

    const today = new Date();
    scheduleDate.min = [
        today.getFullYear(),
        String(today.getMonth() + 1).padStart(2, "0"),
        String(today.getDate()).padStart(2, "0")
    ].join("-");
    scheduleForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (!scheduleForm.reportValidity()) return;

        const scheduleId = scheduleIdInput.value;
        const method = scheduleId ? "PUT" : "POST";
        const payload = {
            work_date: scheduleDate.value,
            start_time: scheduleStart.value,
            end_time: scheduleEnd.value
        };
        if (scheduleId) payload.schedule_id = scheduleId;

        try {
            await requestJson("trainer-schedules.php", {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify(payload)
            });
            resetScheduleForm();
            await loadSchedules();
            showFeedback("Đã lưu lịch làm việc.");
        } catch (error) {
            showFeedback(error.message, true);
        }
    });

    scheduleReset.addEventListener("click", resetScheduleForm);
    schedulesElement.addEventListener("click", async function (event) {
        const editButton = event.target.closest("[data-edit-schedule]");
        if (editButton) {
            scheduleIdInput.value = editButton.dataset.editSchedule;
            scheduleDate.value = editButton.dataset.date;
            scheduleStart.value = editButton.dataset.start;
            scheduleEnd.value = editButton.dataset.end;
            scheduleSubmit.textContent = "Lưu thay đổi";
            scheduleReset.hidden = false;
            scheduleForm.scrollIntoView({ behavior: "smooth", block: "center" });
            return;
        }

        const deactivateButton = event.target.closest("[data-deactivate-schedule]");
        if (!deactivateButton || !window.confirm("Khóa khung giờ này?")) return;
        try {
            await requestJson("trainer-schedules.php", {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify({
                    schedule_id: deactivateButton.dataset.deactivateSchedule
                })
            });
            await loadSchedules();
            showFeedback("Đã khóa khung giờ.");
        } catch (error) {
            showFeedback(error.message, true);
        }
    });

    bookingsElement.addEventListener("click", async function (event) {
        const changeButton = event.target.closest("[data-change-action]");
        if (changeButton) {
            let reason = "";
            if (changeButton.dataset.changeAction === "reject") {
                reason = window.prompt("Nhập lý do từ chối đổi lịch:") || "";
                if (!reason.trim()) return;
            }
            try {
                await requestJson("change-request-action.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": csrfToken
                    },
                    body: JSON.stringify({
                        booking_id: changeButton.dataset.bookingId,
                        request_id: changeButton.dataset.requestId,
                        action: changeButton.dataset.changeAction,
                        reason: reason
                    })
                });
                await loadBookings();
                showFeedback("Đã xử lý yêu cầu đổi lịch.");
            } catch (error) {
                showFeedback(error.message, true);
            }
            return;
        }

        const actionButton = event.target.closest("[data-action]");
        if (!actionButton) return;

        let reason = "";
        if (actionButton.dataset.action === "reject") {
            reason = window.prompt("Nhập lý do từ chối booking:") || "";
            if (!reason.trim()) return;
        } else if (actionButton.dataset.action === "confirm"
                   && !window.confirm("Xác nhận booking này?")) {
            return;
        } else if (actionButton.dataset.action === "complete"
                   && !window.confirm("Đánh dấu buổi tập đã hoàn thành?")) {
            return;
        }

        try {
            await requestJson("trainer-action.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify({
                    booking_id: actionButton.dataset.bookingId,
                    action: actionButton.dataset.action,
                    reason: reason
                })
            });
            await loadBookings();
            showFeedback("Đã cập nhật booking.");
        } catch (error) {
            showFeedback(error.message, true);
        }
    });

    Promise.all([loadSchedules(), loadBookings()]).catch(function (error) {
        showFeedback(error.message, true);
    });
});
</script>
</body>
</html>
