<?php

require_once __DIR__ . '/../../../backend/helpers/auth.php';

/* Bảo vệ trang - chỉ member được vào */
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

/* Redirect đúng role nếu không phải member */
if (!isMember()) {
    $destination = match ($_SESSION['role'] ?? '') {
        'admin', 'staff' => '../admin/bookings.php',
        'trainer'        => '../trainer/pt-bookings.php',
        default          => '../auth/login.php',
    };
    header('Location: ' . $destination);
    exit;
}

/* --- Biến layout dùng chung --- */
$pageTitle   = "Trang chủ";
$sidebarRole = 'member';
$activeNav   = 'dashboard';
$topbarLabel = 'MEMBER AREA';
$topbarTitle = 'Trang chủ';
$csrfToken   = getCsrfToken();
$extraCss    = [];
$extraJs     = ['../../assets/js/api-client.js'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FitZone – Trang chủ hội viên">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | FitZone</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
</head>

<body class="dashboard-page">
<div class="dashboard-layout">

    <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

    <div class="main-area">

        <?php include __DIR__ . '/../../layouts/topbar.php'; ?>

        <main class="dashboard-content">

            <!-- BANNER CHÀO MỪNG -->
            <section class="welcome-banner">
                <div>
                    <p class="banner-label">CHÀO MỪNG ĐẾN VỚI FITZONE</p>
                    <h2>Bắt đầu hành trình khỏe mạnh của bạn!</h2>
                    <p>Quản lý gói tập, tìm huấn luyện viên và chủ động lịch tập.</p>
                    <a href="membership.php" class="primary-link">
                        Khám phá gói tập <span>→</span>
                    </a>
                </div>
                <div class="banner-decoration" aria-hidden="true">FIT</div>
            </section>

            <!-- TỔNG QUAN -->
            <section class="section-block">
                <div class="section-heading">
                    <div>
                        <h2>Tổng quan</h2>
                        <p>Thông tin hoạt động của bạn tại FitZone.</p>
                    </div>
                </div>

                <div class="overview-grid">
                    <article class="overview-card">
                        <div class="card-icon green" aria-hidden="true">▣</div>
                        <p>Gói hội viên</p>
                        <h3 id="membershipStatus">Chưa đăng ký</h3>
                        <span id="membershipDetail">Chọn gói tập phù hợp với bạn</span>
                    </article>

                    <article class="overview-card">
                        <div class="card-icon purple" aria-hidden="true">♙</div>
                        <p>Huấn luyện viên</p>
                        <h3>Khám phá PT</h3>
                        <span>Tìm người đồng hành cùng bạn</span>
                    </article>

                    <article class="overview-card">
                        <div class="card-icon orange" aria-hidden="true">◷</div>
                        <p>Lịch tập sắp tới</p>
                        <h3 id="upcomingCount">Chưa có lịch</h3>
                        <span>Chủ động lên kế hoạch tập luyện</span>
                    </article>
                </div>

                <p class="form-feedback" id="dashboardFeedback" role="status" aria-live="polite"></p>
            </section>

            <!-- TRUY CẬP NHANH -->
            <section class="section-block">
                <div class="section-heading">
                    <div>
                        <h2>Truy cập nhanh</h2>
                        <p>Các chức năng dành cho hội viên.</p>
                    </div>
                </div>

                <div class="quick-grid">
                    <a href="membership.php" class="quick-card">
                        <span class="quick-icon" aria-hidden="true">▣</span>
                        <div>
                            <h3>Xem gói hội viên</h3>
                            <p>Tìm hiểu và đăng ký các gói tập tại FitZone.</p>
                        </div>
                        <span class="quick-arrow" aria-hidden="true">→</span>
                    </a>

                    <a href="../trainer/trainer.php" class="quick-card">
                        <span class="quick-icon" aria-hidden="true">♙</span>
                        <div>
                            <h3>Tìm huấn luyện viên</h3>
                            <p>Xem thông tin và chuyên môn của PT.</p>
                        </div>
                        <span class="quick-arrow" aria-hidden="true">→</span>
                    </a>

                    <a href="../trainer/schedule.php" class="quick-card">
                        <span class="quick-icon" aria-hidden="true">◷</span>
                        <div>
                            <h3>Lịch rảnh PT</h3>
                            <p>Xem các khung giờ PT còn trống để đặt lịch.</p>
                        </div>
                        <span class="quick-arrow" aria-hidden="true">→</span>
                    </a>

                    <a href="../trainer/profile.php" class="quick-card">
                        <span class="quick-icon" aria-hidden="true">⚙</span>
                        <div>
                            <h3>Thông tin cá nhân</h3>
                            <p>Xem và cập nhật thông tin hội viên.</p>
                        </div>
                        <span class="quick-arrow" aria-hidden="true">→</span>
                    </a>
                </div>
            </section>

            <!-- FOOTER -->
            <footer class="dashboard-footer">
                &copy; <?= date('Y') ?> FitZone. All rights reserved.
            </footer>

        </main>

    </div>

</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- CSRF token dùng cho AJAX -->
<input type="hidden" id="pageCsrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/dashboard.js"></script>
<script>
/* Dashboard member: load tóm tắt membership + upcoming bookings */
document.addEventListener("DOMContentLoaded", () => {
    const csrfToken = document.getElementById("pageCsrfToken")?.value || "";
    const api       = new FitzoneAPI("../../../backend/api/", csrfToken);

    /* Membership status */
    async function loadMembership() {
        try {
            const data = await api.get("membership/current.php");
            const statusEl = document.getElementById("membershipStatus");
            const detailEl = document.getElementById("membershipDetail");
            if (data && data.plan_name) {
                if (statusEl) statusEl.textContent = data.plan_name;
                if (detailEl) detailEl.textContent = "Hết hạn: " + FitzoneUI.formatDate(data.end_date);
            } else {
                if (statusEl) statusEl.textContent = "Chưa đăng ký";
                if (detailEl) detailEl.textContent  = "Chọn gói tập phù hợp với bạn";
            }
        } catch (_) {
            /* Giữ placeholder mặc định */
        }
    }

    /* Upcoming bookings count */
    async function loadUpcoming() {
        try {
            const bookings = await api.get("booking/mine.php");
            const upcoming = Array.isArray(bookings)
                ? bookings.filter(b => b.status === "confirmed" || b.status === "pending")
                : [];
            const countEl  = document.getElementById("upcomingCount");
            const detailEl = document.getElementById("upcomingDetail") ?? null;
            if (countEl) {
                countEl.textContent = upcoming.length > 0
                    ? upcoming.length + " lịch tập"
                    : "Chưa có lịch";
            }
        } catch (_) {
            /* Giữ placeholder */
        }
    }

    Promise.all([loadMembership(), loadUpcoming()]);
});
</script>

</body>
</html>
