
<?php
$pageTitle = "Gói hội viên";

$memberships = [
    [
        "name" => "Basic",
        "price" => "399.000",
        "duration" => "1 tháng",
        "description" => "Phù hợp cho người mới bắt đầu tập luyện.",
        "features" => [
            "Sử dụng khu vực tập gym",
            "Hướng dẫn sử dụng thiết bị",
            "Theo dõi tiến độ tập luyện"
        ],
        "popular" => false
    ],
    [
        "name" => "Premium",
        "price" => "999.000",
        "duration" => "3 tháng",
        "description" => "Lựa chọn cân bằng cho việc tập luyện thường xuyên.",
        "features" => [
            "Toàn bộ quyền lợi Basic",
            "Tham gia lớp tập nhóm",
            "Đánh giá thể lực định kỳ"
        ],
        "popular" => true
    ],
    [
        "name" => "VIP",
        "price" => "2.499.000",
        "duration" => "6 tháng",
        "description" => "Trải nghiệm tập luyện với nhiều quyền lợi hơn.",
        "features" => [
            "Toàn bộ quyền lợi Premium",
            "Ưu tiên đặt lịch tập",
            "Tư vấn kế hoạch tập luyện"
        ],
        "popular" => false
    ]
];
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
    <link rel="stylesheet" href="../../assets/css/membership.css">
</head>
<body class="dashboard-page">

<div class="dashboard-layout">

    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="brand">
            <span class="brand-mark">F</span>
            <span>FITZONE</span>
        </a>

        <p class="menu-label">MENU CHÍNH</p>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">⌂</span>
                <span>Trang chủ</span>
            </a>
            <a href="membership.php" class="nav-item active">
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

    <div class="main-area">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle"
                    aria-label="Mở menu" aria-controls="sidebar"
                    aria-expanded="false">☰</button>

            <div class="topbar-title">
                <span class="topbar-label">MEMBER AREA</span>
                <h1>Gói hội viên</h1>
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

            <section class="page-intro">
                <p class="banner-label">CHOOSE YOUR MEMBERSHIP</p>
                <h2>Chọn gói tập phù hợp</h2>
                <p>
                    Khám phá các gói hội viên của FitZone và tìm lựa chọn
                    phù hợp với kế hoạch tập luyện của bạn.
                </p>
            </section>

            <section class="section-block">
                <div class="membership-grid">
                    <?php foreach ($memberships as $plan): ?>
                        <article class="membership-card">
                            <?php if ($plan["popular"]): ?>
                                <span class="popular-label">ĐƯỢC QUAN TÂM</span>
                            <?php endif; ?>

                            <div class="membership-card-heading">
                                <h3><?= htmlspecialchars($plan["name"]) ?></h3>
                                <p><?= htmlspecialchars($plan["description"]) ?></p>
                            </div>

                            <div class="membership-price">
                                <strong>
                                    <?= htmlspecialchars($plan["price"]) ?>
                                </strong>
                                <span>VNĐ</span>
                                <p>/ <?= htmlspecialchars($plan["duration"]) ?></p>
                            </div>

                            <div class="membership-divider"></div>

                            <p class="features-title">Quyền lợi bao gồm</p>
                            <ul class="membership-features">
                                <?php foreach ($plan["features"] as $feature): ?>
                                    <li>
                                        <span class="feature-check">✓</span>
                                        <?= htmlspecialchars($feature) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <a href="../trainer/booking.php" class="membership-button">
                                Chọn gói này <span>→</span>
                            </a>
                        </article>
                    <?php endforeach; ?>
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
</body>
</html>
