
<?php
$pageTitle = "Huấn luyện viên";

$trainers = [
    [
        "name" => "Nguyễn Minh Anh",
        "specialty" => "Strength Training",
        "experience" => "5 năm kinh nghiệm",
        "description" => "Hỗ trợ xây dựng sức mạnh và cải thiện kỹ thuật tập luyện.",
        "initials" => "MA",
        "color" => "trainer-green"
    ],
    [
        "name" => "Trần Quốc Bảo",
        "specialty" => "Fitness & Cardio",
        "experience" => "4 năm kinh nghiệm",
        "description" => "Hướng dẫn các bài tập thể lực và xây dựng thói quen vận động.",
        "initials" => "QB",
        "color" => "trainer-blue"
    ],
    [
        "name" => "Lê Hoàng Phúc",
        "specialty" => "Functional Training",
        "experience" => "6 năm kinh nghiệm",
        "description" => "Hỗ trợ cải thiện khả năng vận động và kỹ thuật bài tập.",
        "initials" => "HP",
        "color" => "trainer-orange"
    ],
    [
        "name" => "Phạm Thu Hà",
        "specialty" => "Yoga & Mobility",
        "experience" => "3 năm kinh nghiệm",
        "description" => "Hướng dẫn các bài tập linh hoạt và cải thiện khả năng vận động.",
        "initials" => "TH",
        "color" => "trainer-purple"
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
            <a href="../member/dashboard.php" class="nav-item">
                <span class="nav-icon">⌂</span>
                <span>Trang chủ</span>
            </a>
            <a href="../member/membership.php" class="nav-item">
                <span class="nav-icon">▣</span>
                <span>Gói hội viên</span>
            </a>
            <a href="trainers.php" class="nav-item active">
                <span class="nav-icon">♙</span>
                <span>Huấn luyện viên</span>
            </a>
            <a href="booking.php" class="nav-item">
                <span class="nav-icon">▦</span>
                <span>Đặt lịch tập</span>
            </a>
            <a href="schedule.php" class="nav-item">
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
                <h1>Huấn luyện viên</h1>
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
                <p class="banner-label">MEET OUR TRAINERS</p>
                <h2>Tìm huấn luyện viên phù hợp</h2>
                <p>
                    Tìm hiểu chuyên môn của từng PT và lựa chọn người
                    đồng hành cùng kế hoạch tập luyện của bạn.
                </p>
            </section>

            <section class="section-block">
                <div class="section-heading">
                    <div>
                        <h2>Đội ngũ huấn luyện viên</h2>
                        <p><?= count($trainers) ?> huấn luyện viên trong dữ liệu mẫu</p>
                    </div>
                </div>

                <div class="trainer-grid">
                    <?php foreach ($trainers as $trainer): ?>
                        <article class="trainer-card">
                            <div class="trainer-avatar <?= htmlspecialchars($trainer["color"]) ?>">
                                <?= htmlspecialchars($trainer["initials"]) ?>
                            </div>

                            <div class="trainer-info">
                                <span class="trainer-tag">PERSONAL TRAINER</span>
                                <h3><?= htmlspecialchars($trainer["name"]) ?></h3>
                                <p class="trainer-specialty">
                                    <?= htmlspecialchars($trainer["specialty"]) ?>
                                </p>
                                <p class="trainer-experience">
                                    <?= htmlspecialchars($trainer["experience"]) ?>
                                </p>
                                <p class="trainer-description">
                                    <?= htmlspecialchars($trainer["description"]) ?>
                                </p>
                            </div>

                            <a href="booking.php" class="trainer-button">
                                Xem lịch / Đặt lịch <span>→</span>
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
