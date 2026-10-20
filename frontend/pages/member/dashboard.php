
<?php
$pageTitle = "Trang chủ";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | FitZone</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
</head>

<body class="dashboard-page">

<div class="dashboard-layout">

    <!-- MENU BÊN TRÁI -->
    <aside class="sidebar" id="sidebar">

        <a href="dashboard.php" class="brand">
            <span class="brand-mark">F</span>
            <span>FITZONE</span>
        </a>

        <p class="menu-label">MENU CHÍNH</p>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="nav-item active">
                <span class="nav-icon">⌂</span>
                <span>Trang chủ</span>
            </a>

            <a href="membership.php" class="nav-item">
                <span class="nav-icon">▣</span>
                <span>Gói hội viên</span>
            </a>

            <a href="../trainer/trainer.php" class="nav-item">
                <span class="nav-icon">♙</span>
                <span>Huấn luyện viên</span>
            </a>

            <a href="../trainer/booking.php" class="nav-item">
                <span class="nav-icon">▦</span>
                <span>Đặt lịch tập</span>
            </a>

            <a href="../trainer/schedule.php" class="nav-item">
                <span class="nav-icon">◷</span>
                <span>Lịch PT</span>
            </a>

            <a href="profile.php" class="nav-item">
                <span class="nav-icon">⚙</span>
                <span>Thông tin cá nhân</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <a href="../auth/login.php" class="nav-item logout-link">
                <span class="nav-icon">↪</span>
                <span>Đăng xuất</span>
            </a>
        </div>

    </aside>

    <!-- KHU VỰC NỘI DUNG -->
    <div class="main-area">

        <!-- THANH TRÊN CÙNG -->
        <header class="topbar">

            <button
                type="button"
                class="menu-toggle"
                id="menuToggle"
                aria-label="Mở menu"
                aria-controls="sidebar"
                aria-expanded="false"
            >☰</button>

            <div class="topbar-title">
                <span class="topbar-label">MEMBER AREA</span>
                <h1>Trang chủ</h1>
            </div>

            <div class="user-info">
                <div class="user-avatar">HV</div>

                <div class="user-text">
                    <strong>Hội viên</strong>
                    <span>Chào mừng bạn!</span>
                </div>
            </div>

        </header>

        <main class="dashboard-content">

            <!-- BANNER CHÀO MỪNG -->
            <section class="welcome-banner">

                <div>
                    <p class="banner-label">CHÀO MỪNG ĐẾN VỚI FITZONE</p>

                    <h2>Bắt đầu hành trình khỏe mạnh của bạn!</h2>

                    <p>
                        Quản lý gói tập, tìm huấn luyện viên
                        và chủ động lịch tập.
                    </p>

                    <a href="membership.php" class="primary-link">
                        Khám phá gói tập <span>→</span>
                    </a>
                </div>

                <div class="banner-decoration" aria-hidden="true">
                    FIT
                </div>

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
                        <div class="card-icon green">▣</div>
                        <p>Gói hội viên</p>
                        <h3>Chưa đăng ký</h3>
                        <span>Chọn gói tập phù hợp với bạn</span>
                    </article>

                    <article class="overview-card">
                        <div class="card-icon purple">♙</div>
                        <p>Huấn luyện viên</p>
                        <h3>Khám phá PT</h3>
                        <span>Tìm người đồng hành cùng bạn</span>
                    </article>

                    <article class="overview-card">
                        <div class="card-icon orange">◷</div>
                        <p>Lịch tập sắp tới</p>
                        <h3>Chưa có lịch</h3>
                        <span>Chủ động lên kế hoạch tập luyện</span>
                    </article>

                </div>

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
                        <span class="quick-icon">▣</span>

                        <div>
                            <h3>Xem gói hội viên</h3>
                            <p>Tìm hiểu các gói tập tại FitZone.</p>
                        </div>

                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="../trainer/trainer.php" class="quick-card">
                        <span class="quick-icon">♙</span>

                        <div>
                            <h3>Tìm huấn luyện viên</h3>
                            <p>Xem thông tin và chuyên môn của PT.</p>
                        </div>

                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="../trainer/schedule.php" class="quick-card">
                        <span class="quick-icon">◷</span>

                        <div>
                            <h3>Lịch rảnh PT</h3>
                            <p>Xem các khung giờ PT còn trống để đặt lịch.</p>
                        </div>

                        <span class="quick-arrow">→</span>
                    </a>


                    <a href="profile.php" class="quick-card">
                        <span class="quick-icon">⚙</span>

                        <div>
                            <h3>Thông tin cá nhân</h3>
                            <p>Xem và cập nhật thông tin hội viên.</p>
                        </div>

                        <span class="quick-arrow">→</span>
                    </a>

                </div>

            </section>

            <!-- CHÂN TRANG -->
            <footer class="dashboard-footer">
                © <?= date("Y") ?> FitZone. All rights reserved.
            </footer>

        </main>

    </div>

</div>

<!-- LỚP PHỦ MENU TRÊN ĐIỆN THOẠI -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="../../assets/js/dashboard.js"></script>

</body>
</html>
