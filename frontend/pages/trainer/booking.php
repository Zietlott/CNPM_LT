
<?php
$pageTitle = "Đặt lịch tập";

$trainers = [
    "Nguyễn Minh Anh",
    "Trần Quốc Bảo",
    "Lê Hoàng Phúc",
    "Phạm Thu Hà"
];

$timeSlots = [
    "07:00 - 08:00",
    "08:00 - 09:00",
    "09:00 - 10:00",
    "14:00 - 15:00",
    "15:00 - 16:00",
    "16:00 - 17:00",
    "17:00 - 18:00"
];
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
                <div class="user-text"><strong>Hội viên</strong><span>Chào mừng bạn!</span></div>
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
                            <select class="form-select" id="trainer" name="trainer" required>
                                <option value="">-- Chọn huấn luyện viên --</option>
                                <?php foreach ($trainers as $trainer): ?>
                                    <option value="<?= htmlspecialchars($trainer) ?>">
                                        <?= htmlspecialchars($trainer) ?>
                                    </option>
                                <?php endforeach; ?>
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
                                <?php foreach ($timeSlots as $slot): ?>
                                    <option value="<?= htmlspecialchars($slot) ?>">
                                        <?= htmlspecialchars($slot) ?>
                                    </option>
                                <?php endforeach; ?>
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
                    </form>
                </div>

                <aside class="booking-info-card">
                    <div class="booking-info-icon">◷</div>
                    <h3>Lưu ý khi đặt lịch</h3>
                    <ul>
                        <li>Kiểm tra ngày và khung giờ trước khi gửi yêu cầu.</li>
                        <li>Lịch tập cần được xác nhận trước khi đến tập.</li>
                        <li>Liên hệ phòng tập nếu bạn muốn thay đổi lịch đã đặt.</li>
                    </ul>
                    <div class="booking-info-note">
                        <strong>Trạng thái đặt lịch</strong>
                        <p>Yêu cầu demo sẽ được thông báo ngay trên giao diện. Chức năng lưu lịch sẽ được bổ sung khi tích hợp backend.</p>
                    </div>
                </aside>
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

    // Không cho chọn ngày trong quá khứ.
    const now = new Date();
    const localDate = [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, "0"),
        String(now.getDate()).padStart(2, "0")
    ].join("-");

    dateInput.min = localDate;

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        feedback.textContent =
            "Đã kiểm tra thông tin. Đây là bản demo, lịch chưa được lưu vào hệ thống.";
        feedback.classList.add("is-visible");
    });
});
</script>
</body>
</html>
