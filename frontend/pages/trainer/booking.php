<?php
require_once __DIR__ . '/../../../backend/helpers/auth.php';

if (!isMember()) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Đặt lịch tập';
$sidebarRole = 'member';
$activeNav = 'booking';
$topbarLabel = 'MEMBER AREA';
$topbarTitle = $pageTitle;
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

    <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

    <div class="main-area">
        <?php include __DIR__ . '/../../layouts/topbar.php'; ?>

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
                            <label for="bookingDuration" class="form-label">Thời lượng (phút)</label>
                            <select class="form-select" id="bookingDuration">
                                <?php for ($minutes = 30; $minutes <= 180; $minutes += 15): ?>
                                    <option value="<?= $minutes ?>" <?= $minutes === 60 ? 'selected' : '' ?>><?= $minutes ?></option>
                                <?php endfor; ?>
                            </select>
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
                <div class="booking-list-toolbar">
                    <label for="bookingFilter" class="form-label">Lọc lịch tập</label>
                    <select id="bookingFilter" class="form-select" aria-controls="myBookings">
                        <option value="all">Tất cả</option>
                        <option value="change">Yêu cầu đổi lịch</option>
                        <option value="awaitingpayment">Chờ thanh toán</option>
                        <option value="pending">Chờ xác nhận</option>
                        <option value="confirmed">Đã xác nhận</option>
                        <option value="completed">Đã hoàn thành</option>
                        <option value="closed">Đã hủy hoặc từ chối</option>
                    </select>
                </div>
                <div id="myBookings" class="booking-list" aria-live="polite">
                    <p class="booking-list-empty">Đang tải lịch tập...</p>
                </div>
            </section>

            <dialog id="changeBookingDialog" class="booking-dialog" aria-labelledby="changeBookingTitle">
                <form id="changeBookingForm">
                    <div class="booking-dialog-heading">
                        <div>
                            <p class="banner-label">RESCHEDULE REQUEST</p>
                            <h2 id="changeBookingTitle">Yêu cầu đổi lịch</h2>
                        </div>
                        <button type="button" class="booking-dialog-close"
                                data-close-dialog aria-label="Đóng">×</button>
                    </div>
                    <input type="hidden" id="changeBookingId">
                    <p class="booking-change-pending"><strong>Lịch hiện tại</strong><br><span id="currentBookingSlot"></span></p>
                    <div class="mb-3">
                        <label for="changeTrainer" class="form-label">Huấn luyện viên hiện tại</label>
                        <select id="changeTrainer" class="form-select" disabled></select>
                    </div>
                    <div class="mb-3">
                        <label for="changeDate" class="form-label">Ngày mới</label>
                        <input type="date" id="changeDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="changeSlot" class="form-label">Khung giờ mới</label>
                        <select id="changeSlot" class="form-select" required>
                            <option value="">-- Chọn ngày trước --</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="changeReason" class="form-label">Lý do đổi lịch</label>
                        <textarea id="changeReason" class="form-control" maxlength="255"
                                  rows="3" required></textarea>
                    </div>
                    <div class="booking-dialog-actions">
                        <button type="button" class="booking-dialog-secondary"
                                data-close-dialog>Đóng</button>
                        <button type="submit" class="booking-submit-button">
                            Gửi yêu cầu <span>→</span>
                        </button>
                    </div>
                </form>
            </dialog>

            <footer class="dashboard-footer">
                © <?= date("Y") ?> FitZone. All rights reserved.
            </footer>
        </main>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php include __DIR__ . '/../../layouts/booking-confirmation.php'; ?>
<script src="../../assets/js/dashboard.js"></script>
<script src="../../assets/js/booking-confirmation.js"></script>
<script src="../../assets/js/booking-history.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const dateInput = document.getElementById("bookingDate");
    const form = document.getElementById("bookingForm");
    const feedback = document.getElementById("bookingFeedback");
    const trainerSelect = document.getElementById("trainer");
    const timeSlotSelect = document.getElementById("timeSlot");
    const bookingList = document.getElementById("myBookings");
    const changeDialog = document.getElementById("changeBookingDialog");
    const changeForm = document.getElementById("changeBookingForm");
    const changeTrainerSelect = document.getElementById("changeTrainer");
    const changeDateInput = document.getElementById("changeDate");
    const changeSlotSelect = document.getElementById("changeSlot");
    const durationSelect = document.getElementById("bookingDuration");
    let changeDuration = 60;
    const csrfToken = document.getElementById("bookingCsrfToken").value;
    const apiBase = "../../../backend/api/booking/";
    const statusLabels = {
        awaitingpayment: "Chờ thanh toán",
        expired: "Hết hạn giữ chỗ",
        pending: "Chờ xác nhận",
        confirmed: "Đã xác nhận",
        rejected: "Đã từ chối",
        cancelled: "Đã hủy",
        completed: "Đã hoàn thành"
    };
    let trainers = [];

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
        trainers = data.trainers;
        trainerSelect.innerHTML = '<option value="">-- Chọn huấn luyện viên --</option>';
        changeTrainerSelect.innerHTML = "";

        data.trainers.forEach(function (trainer) {
            const option = document.createElement("option");
            option.value = trainer.trainer_id;
            option.textContent = trainer.full_name + " · " + trainer.specialty;
            trainerSelect.appendChild(option);
            changeTrainerSelect.appendChild(option.cloneNode(true));
        });

        if (data.trainers.length === 0) {
            trainerSelect.options[0].textContent = "Hiện chưa có huấn luyện viên có lịch trống";
        }
    }

    async function loadSlots() {
        await loadSlotsFor(
            trainerSelect.value,
            dateInput.value,
            timeSlotSelect,
            "-- Chọn khung giờ --"
        );
    }

    async function loadSlotsFor(trainerId, workDate, select, placeholder) {
        select.innerHTML = "";
        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.textContent = placeholder;
        select.appendChild(defaultOption);

        if (!trainerId || !workDate) return;

        const params = new URLSearchParams({
            trainer_id: trainerId,
            work_date: workDate
        });
        const data = await requestJson(apiBase + "availability.php?" + params.toString());
        const isChange = select === changeSlotSelect;
        const duration = isChange ? changeDuration : Number(durationSelect.value);
        const minutes = (time) => Number(time.slice(0, 2)) * 60 + Number(time.slice(3, 5));
        const hhmm = (time) => String(Math.floor(time / 60)).padStart(2, "0") + ":" +
            String(time % 60).padStart(2, "0");

        if (select !== changeSlotSelect && (trainerSelect.value !== trainerId || dateInput.value !== workDate)) return;
        if (isChange && (changeTrainerSelect.value !== trainerId || changeDateInput.value !== workDate)) return;
        select.replaceChildren(defaultOption);
        for (const slot of data.slots) {
            for (let from = minutes(slot.start_time); from + duration <= minutes(slot.end_time); from += 15) {
                const start = hhmm(from);
                const end = hhmm(from + duration);
                if (Date.parse(workDate + "T" + start + ":00+07:00") < Date.now() + 86400000) continue;
                const option = document.createElement("option");
                option.value = slot.schedule_id + "|" + start;
                option.dataset.scheduleId = slot.schedule_id;
                option.dataset.startAt = workDate + "T" + start;
                option.dataset.endAt = workDate + "T" + end;
                option.textContent = start + " - " + end;
                if (!isChange) {
                    option.textContent += " · " + (Number(slot.hourly_rate) * duration / 60)
                        .toLocaleString("vi-VN", { maximumFractionDigits: 2 }) + " ₫";
                }
                select.appendChild(option);
            }
        }

        if (select.options.length === 1) {
            defaultOption.textContent = "Ngày này không còn khung giờ trống";
        }
    }

    const bookingFilter = document.getElementById("bookingFilter");
    let allBookings = null;

    bookingFilter.addEventListener("change", renderBookings);

    async function loadBookings() {
        allBookings = await requestJson(apiBase + "mine.php");
        renderBookings();
    }

    function renderBookings() {
        if (allBookings === null) return;

        const filter = bookingFilter.value;
        const bookings = allBookings.filter(function (booking) {
            if (filter === "all") return true;
            if (filter === "change") return Boolean(booking.pending_change_request_id);
            if (filter === "closed") return ["cancelled", "rejected", "expired"].includes(booking.status);
            return booking.status === filter;
        });

        if (bookings.length === 0 && allBookings.length > 0) {
            bookingList.innerHTML = '<p class="booking-list-empty">Không có lịch tập phù hợp với bộ lọc.</p>';
            return;
        }
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
            const paymentButton = booking.status === "awaitingpayment"
                ? '<button type="button" class="booking-management-button" data-pay-id="' +
                    escapeHtml(booking.booking_id) + '">Thanh toán demo</button>'
                : "";
            const paymentDetails = booking.status === "awaitingpayment"
                ? '<p class="booking-change-pending">Phí: ' +
                    escapeHtml(Number(booking.fee_snapshot).toLocaleString("vi-VN")) + ' ₫ · Giữ đến ' +
                    escapeHtml(new Date(booking.hold_expires_at.replace(" ", "T") + "Z")
                        .toLocaleTimeString("vi-VN", { hour: "2-digit", minute: "2-digit", timeZone: "Asia/Ho_Chi_Minh" })) +
                    '</p>'
                : "";
            const changeRequest = booking.pending_change_request_id
                ? '<p class="booking-change-pending">Đang chờ PT duyệt đổi lịch: ' +
                    escapeHtml(booking.pending_change_request_reason) +
                    '</p><button type="button" class="booking-change-cancel-button" data-cancel-change-id="' +
                    escapeHtml(booking.booking_id) + '" data-request-id="' +
                    escapeHtml(booking.pending_change_request_id) +
                    '">Rút yêu cầu đổi lịch</button>'
                : "";
            const changeButton = !booking.pending_change_request_id
                && Number(booking.can_request_change) === 1
                ? '<button type="button" class="booking-change-button" data-change-id="' +
                    escapeHtml(booking.booking_id) + '" data-trainer-id="' +
                    escapeHtml(booking.trainer_id) + '" data-work-date="' +
                    escapeHtml(booking.work_date) + '" data-duration="' +
                    escapeHtml(booking.duration_minutes) + '">Đổi lịch</button>'
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
                    "</p>" + note + cancellation + paymentDetails + renderChangeHistory(booking.change_history) +
                "</div><div class=\"booking-actions\">" +
                    paymentButton + changeRequest + changeButton + cancelButton +
                "</div>" +
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
    durationSelect.addEventListener("change", refreshAvailability);

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        const slot = timeSlotSelect.selectedOptions[0];
        submitButton.disabled = true;

        try {
            await requestJson(apiBase + "create.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify({
                    schedule_id: slot.dataset.scheduleId,
                    startAt: slot.dataset.startAt,
                    endAt: slot.dataset.endAt,
                    note: document.getElementById("note").value
                })
            });

            document.getElementById("note").value = "";
            try {
                await Promise.all([loadBookings(), loadSlots()]);
                showFeedback("Đã giữ chỗ trong 15 phút. Chọn Thanh toán demo trong Lịch tập của tôi.");
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
        const payButton = event.target.closest("[data-pay-id]");
        if (payButton) {
            payButton.disabled = true;
            try {
                await requestJson(apiBase + "pay-demo.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-Token": csrfToken },
                    body: JSON.stringify({ booking_id: payButton.dataset.payId })
                });
                await loadBookings();
                showFeedback("Đã thanh toán demo. Lịch đang chờ PT xác nhận.");
            } catch (error) {
                payButton.disabled = false;
                showFeedback(error.message, true);
            }
            return;
        }
        const changeButton = event.target.closest("[data-change-id]");
        if (changeButton) {
            changeDuration = Number(changeButton.dataset.duration);
            document.getElementById("changeBookingId").value = changeButton.dataset.changeId;
            changeTrainerSelect.value = changeButton.dataset.trainerId;
            document.getElementById("currentBookingSlot").textContent =
                changeButton.closest(".booking-list-item").querySelector(".booking-slot-detail").textContent;
            changeDateInput.min = localDate;
            changeDateInput.value = changeButton.dataset.workDate;
            document.getElementById("changeReason").value = "";
            changeDialog.showModal();
            try {
                await loadSlotsFor(
                    changeTrainerSelect.value,
                    changeDateInput.value,
                    changeSlotSelect,
                    "-- Chọn khung giờ mới --"
                );
            } catch (error) {
                showFeedback(error.message, true);
            }
            return;
        }

        const cancelChangeButton = event.target.closest("[data-cancel-change-id]");
        if (cancelChangeButton) {
            try {
                await requestJson(apiBase + "change-request-action.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": csrfToken
                    },
                    body: JSON.stringify({
                        booking_id: cancelChangeButton.dataset.cancelChangeId,
                        request_id: cancelChangeButton.dataset.requestId,
                        action: "cancel"
                    })
                });
                await loadBookings();
                showFeedback("Đã rút yêu cầu đổi lịch.");
            } catch (error) {
                showFeedback(error.message, true);
            }
            return;
        }

        const cancelButton = event.target.closest("[data-cancel-id]");

        if (!cancelButton) return;
        const confirmation = await confirmBookingAction({
            heading: "Hủy lịch tập?",
            detail: "Khung giờ sẽ được mở lại cho hội viên khác sau khi hủy.",
            action: "Hủy lịch",
            danger: true
        });
        if (confirmation === null) return;

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

    changeDateInput.addEventListener("change", async function () {
        try {
            await loadSlotsFor(
                changeTrainerSelect.value,
                changeDateInput.value,
                changeSlotSelect,
                "-- Chọn khung giờ mới --"
            );
        } catch (error) {
            changeSlotSelect.innerHTML = '<option value="">Không thể tải khung giờ</option>';
            showFeedback(error.message, true);
        }
    });

    changeForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        if (!changeForm.reportValidity()) return;

        const submitButton = changeForm.querySelector('button[type="submit"]');
        const slot = changeSlotSelect.selectedOptions[0];
        submitButton.disabled = true;
        try {
            await requestJson(apiBase + "change-requests.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify({
                    booking_id: document.getElementById("changeBookingId").value,
                    schedule_id: slot.dataset.scheduleId,
                    newStartAt: slot.dataset.startAt,
                    newEndAt: slot.dataset.endAt,
                    reason: document.getElementById("changeReason").value
                })
            });
            changeDialog.close();
            await loadBookings();
            showFeedback("Đã gửi yêu cầu đổi lịch. Booking cũ vẫn được giữ đến khi PT duyệt.");
        } catch (error) {
            showFeedback(error.message, true);
        } finally {
            submitButton.disabled = false;
        }
    });

    changeDialog.querySelectorAll("[data-close-dialog]").forEach(function (button) {
        button.addEventListener("click", function () {
            changeDialog.close();
        });
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
