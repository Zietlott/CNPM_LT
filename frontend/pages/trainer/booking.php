
<?php
require_once __DIR__ . '/../../../backend/helpers/auth.php';

if (!isMember()) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Đặt lịch tập';
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | FitZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
    <link href="../../assets/css/booking.css" rel="stylesheet">
</head>
<body class="dashboard-page">
<div class="dashboard-layout">

    <aside class="sidebar" id="sidebar">
        <a href="../member/dashboard.php" class="brand">
            <span class="brand-mark">F</span><span>FITZONE</span>
        </a>
        <p class="menu-label">MENU CHÍNH</p>
        <nav class="sidebar-nav">
            <a href="../member/dashboard.php" class="nav-item"><span class="nav-icon">⌂</span><span>Trang chủ</span></a>
            <a href="../member/membership.php" class="nav-item"><span class="nav-icon">▣</span><span>Gói hội viên</span></a>
            <a href="trainer.php" class="nav-item"><span class="nav-icon">♙</span><span>Huấn luyện viên</span></a>
            <a href="booking.php" class="nav-item active"><span class="nav-icon">▦</span><span>Đặt lịch tập</span></a>
            <a href="schedule.php" class="nav-item"><span class="nav-icon">◷</span><span>Lịch PT</span></a>
            <a href="../member/profile.php" class="nav-item"><span class="nav-icon">⚙</span><span>Thông tin cá nhân</span></a>
        </nav>
        <div class="sidebar-bottom">
            <a href="../auth/login.php" class="nav-item logout-link"><span class="nav-icon">↪</span><span>Đăng xuất</span></a>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle"
                    aria-label="Mở menu" aria-controls="sidebar" aria-expanded="false">☰</button>
            <div class="topbar-title">
                <span class="topbar-label">MEMBER AREA</span>
                <h1>Đặt lịch tập</h1>
            </div>
            <div class="user-info">
                <div class="user-avatar">HV</div>
                <div class="user-text">
                    <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Hội viên', ENT_QUOTES, 'UTF-8') ?></strong>
                    <span>Chào mừng bạn!</span>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <section class="page-intro">
                <p class="banner-label">BOOK YOUR SESSION</p>
                <h2>Lên lịch tập cùng PT</h2>
                <p>Chọn huấn luyện viên, ngày và khung giờ phù hợp với lịch trình của bạn.</p>
            </section>

            <section class="section-block booking-layout">
                <div class="booking-form-card">
                    <h2>Thông tin đặt lịch</h2>
                    <p class="booking-subtitle">Vui lòng điền đầy đủ thông tin bên dưới.</p>

                    <form id="bookingForm">
                        <div class="mb-3">
                            <label for="trainer" class="form-label">Huấn luyện viên</label>
                            <select class="form-select" id="trainer" name="trainer_id" required>
                                <option value="">-- Chọn huấn luyện viên --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="bookingDate" class="form-label">Ngày tập</label>
                            <input class="form-control" type="date" id="bookingDate"
                                   name="booking_date" required>
                        </div>

                        <div class="mb-3">
                            <label for="timeSlot" class="form-label">Khung giờ</label>
                            <select class="form-select" id="timeSlot" name="time_slot" required>
                                <option value="">-- Chọn khung giờ --</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="note" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="note" name="note" rows="4"
                                      maxlength="500"
                                      placeholder="Ví dụ: Nội dung muốn tập hoặc lưu ý cho PT..."></textarea>
                        </div>

                        <button type="submit" class="booking-submit-button">
                            Gửi yêu cầu đặt lịch <span>→</span>
                        </button>

                        <p id="bookingFeedback" class="booking-feedback"
                           role="status" aria-live="polite"></p>
                        <input type="hidden" id="bookingCsrfToken"
                               value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    </form>
                </div>

                <aside class="booking-info-card">
                    <div class="booking-info-icon">◷</div>
                    <h3>Lưu ý khi đặt lịch</h3>
                    <ul>
                        <li>Kiểm tra ngày và khung giờ trước khi gửi yêu cầu.</li>
                        <li>Gói hội viên của bạn cần còn hiệu lực vào ngày tập.</li>
                        <li>Yêu cầu đặt lịch ở trạng thái chờ đến khi được xác nhận.</li>
                        <li>Bạn chỉ có thể tự hủy trước giờ tập ít nhất 24 tiếng.</li>
                    </ul>
                    <div class="booking-info-note">
                        <strong>Trạng thái đặt lịch</strong>
                        <p>Lịch đặt được lưu vào hệ thống. Khung giờ đang chờ xác nhận hoặc đã xác nhận sẽ không thể được hội viên khác đặt trùng.</p>
                    </div>
                </aside>
            </section>

            <section class="section-block booking-history">
                <div class="booking-history-heading">
                    <div>
                        <p class="banner-label">BOOKING HISTORY</p>
                        <h2>Lịch tập của tôi</h2>
                    </div>
                </div>
                <div id="myBookings" class="booking-list" aria-live="polite">
                    <p class="booking-list-empty">Đang tải lịch tập...</p>
                </div>
            </section>

            <footer class="dashboard-footer">
                © <?= date("Y") ?> FitZone. All rights reserved.
            </footer>
        </main>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script src="../../assets/js/dashboard.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const dateInput = document.getElementById("bookingDate");
    const form = document.getElementById("bookingForm");
    const feedback = document.getElementById("bookingFeedback");
    const trainerSelect = document.getElementById("trainer");
    const timeSlotSelect = document.getElementById("timeSlot");
    const bookingList = document.getElementById("myBookings");
    const csrfToken = document.getElementById("bookingCsrfToken").value;
    const apiBase = "../../../backend/api/booking/";
    const statusLabels = {
        pending: "Chờ xác nhận",
        confirmed: "Đã xác nhận",
        rejected: "Đã từ chối",
        cancelled: "Đã hủy",
        completed: "Đã hoàn thành"
    };

    async function requestJson(url, options = {}) {
        const response = await fetch(url, options);
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

    function showFeedback(message, isError = false) {
        feedback.textContent = message;
        feedback.classList.toggle("is-error", isError);
        feedback.classList.add("is-visible");
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

    function formatDate(value) {
        const date = new Date(value + "T00:00:00");
        return new Intl.DateTimeFormat("vi-VN").format(date);
    }

    async function loadTrainers() {
        const data = await requestJson(apiBase + "availability.php");
        trainerSelect.innerHTML = '<option value="">-- Chọn huấn luyện viên --</option>';

        data.trainers.forEach(function (trainer) {
            const option = document.createElement("option");
            option.value = trainer.trainer_id;
            option.textContent = trainer.full_name + " · " + trainer.specialty;
            trainerSelect.appendChild(option);
        });

        if (data.trainers.length === 0) {
            trainerSelect.options[0].textContent = "Hiện chưa có huấn luyện viên có lịch trống";
        }
    }

    async function loadSlots() {
        const trainerId = trainerSelect.value;
        const workDate = dateInput.value;
        timeSlotSelect.innerHTML = '<option value="">-- Chọn khung giờ --</option>';

        if (!trainerId || !workDate) {
            return;
        }

        const params = new URLSearchParams({
            trainer_id: trainerId,
            work_date: workDate
        });
        const data = await requestJson(apiBase + "availability.php?" + params.toString());

        data.slots.forEach(function (slot) {
            const option = document.createElement("option");
            option.value = slot.schedule_id;
            option.textContent =
                slot.start_time.slice(0, 5) + " - " + slot.end_time.slice(0, 5);
            timeSlotSelect.appendChild(option);
        });

        if (data.slots.length === 0) {
            timeSlotSelect.options[0].textContent = "Ngày này không còn khung giờ trống";
        }
    }

    async function loadBookings() {
        const bookings = await requestJson(apiBase + "mine.php");

        if (bookings.length === 0) {
            bookingList.innerHTML = '<p class="booking-list-empty">Bạn chưa có lịch tập nào.</p>';
            return;
        }

        bookingList.innerHTML = bookings.map(function (booking) {
            const canCancel = Number(booking.can_cancel) === 1;
            const statusLabel = statusLabels[booking.status] || "Không xác định";
            const cancellation = booking.cancel_reason
                ? '<p class="booking-cancel-reason"><strong>Lý do:</strong> ' +
                    escapeHtml(booking.cancel_reason) + "</p>"
                : "";
            const note = booking.note
                ? '<p class="booking-member-note"><strong>Ghi chú:</strong> ' +
                    escapeHtml(booking.note) + "</p>"
                : "";
            const cancelButton = canCancel
                ? '<button type="button" class="booking-cancel-button" data-cancel-id="' +
                    escapeHtml(booking.booking_id) + '">Hủy lịch</button>'
                : "";

            return '<article class="booking-list-item">' +
                '<div class="booking-list-item-main">' +
                    '<div class="booking-list-item-heading">' +
                        "<h3>" + escapeHtml(booking.trainer_name) + "</h3>" +
                        '<span class="booking-status booking-status-' +
                            escapeHtml(booking.status) + '">' +
                            escapeHtml(statusLabel) + "</span>" +
                    "</div>" +
                    '<p class="booking-slot-detail">' +
                        escapeHtml(booking.specialty) + " · " +
                        escapeHtml(formatDate(booking.work_date)) + " · " +
                        escapeHtml(booking.start_time.slice(0, 5)) + " - " +
                        escapeHtml(booking.end_time.slice(0, 5)) +
                    "</p>" + note + cancellation +
                "</div>" + cancelButton +
            "</article>";
        }).join("");
    }

    async function refreshAvailability() {
        try {
            await loadSlots();
        } catch (error) {
            timeSlotSelect.innerHTML = '<option value="">Không thể tải khung giờ</option>';
            showFeedback(error.message, true);
        }
    }

    const now = new Date();
    const localDate = [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, "0"),
        String(now.getDate()).padStart(2, "0")
    ].join("-");

    dateInput.min = localDate;
    trainerSelect.addEventListener("change", refreshAvailability);
    dateInput.addEventListener("change", refreshAvailability);

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            await requestJson(apiBase + "create.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify({
                    schedule_id: timeSlotSelect.value,
                    note: document.getElementById("note").value
                })
            });

            document.getElementById("note").value = "";
            try {
                await Promise.all([loadBookings(), loadSlots()]);
                showFeedback("Đã gửi yêu cầu đặt lịch. Vui lòng chờ xác nhận.");
            } catch (refreshError) {
                showFeedback(
                    "Đã lưu yêu cầu đặt lịch nhưng không thể tải lại danh sách: " +
                    refreshError.message,
                    true
                );
            }
        } catch (error) {
            showFeedback(error.message, true);
            refreshAvailability();
        } finally {
            submitButton.disabled = false;
        }
    });

    bookingList.addEventListener("click", async function (event) {
        const cancelButton = event.target.closest("[data-cancel-id]");

        if (!cancelButton || !window.confirm("Bạn có chắc muốn hủy lịch tập này?")) {
            return;
        }

        cancelButton.disabled = true;
        try {
            await requestJson(apiBase + "cancel.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify({
                    booking_id: cancelButton.dataset.cancelId
                })
            });

            try {
                await Promise.all([loadBookings(), loadSlots()]);
                showFeedback("Đã hủy lịch tập thành công.");
            } catch (refreshError) {
                showFeedback(
                    "Đã hủy lịch nhưng không thể tải lại danh sách: " +
                    refreshError.message,
                    true
                );
            }
        } catch (error) {
            showFeedback(error.message, true);
            cancelButton.disabled = false;
        }
    });

    loadTrainers().catch(function (error) {
        trainerSelect.options[0].textContent = "Không thể tải huấn luyện viên";
        showFeedback(error.message, true);
    });
    loadBookings().catch(function (error) {
        bookingList.textContent = error.message;
    });
});
</script>
</body>
</html>
