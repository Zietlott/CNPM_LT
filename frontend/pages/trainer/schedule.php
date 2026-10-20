
<?php
$pageTitle = "Lịch rảnh PT";

/*
 * Dữ liệu mẫu lịch rảnh của PT.
 * Có thể thay bằng dữ liệu lấy từ database sau này.
 */
$ptSchedules = [
    [
        "date" => "2026-10-21",
        "start_time" => "08:00",
        "end_time" => "09:00",
        "trainer" => "Nguyễn Minh Anh",
        "specialty" => "Strength Training",
        "status" => "Còn trống"
    ],
    [
        "date" => "2026-10-21",
        "start_time" => "09:00",
        "end_time" => "10:00",
        "trainer" => "Trần Quốc Bảo",
        "specialty" => "Fitness & Cardio",
        "status" => "Đã kín lịch"
    ],
    [
        "date" => "2026-10-22",
        "start_time" => "15:00",
        "end_time" => "16:00",
        "trainer" => "Trần Quốc Bảo",
        "specialty" => "Fitness & Cardio",
        "status" => "Còn trống"
    ],
    [
        "date" => "2026-10-23",
        "start_time" => "09:00",
        "end_time" => "10:00",
        "trainer" => "Lê Hoàng Phúc",
        "specialty" => "Functional Training",
        "status" => "Còn trống"
    ],
    [
        "date" => "2026-10-24",
        "start_time" => "14:00",
        "end_time" => "15:00",
        "trainer" => "Phạm Thu Hà",
        "specialty" => "Weight Loss & Fitness",
        "status" => "Đã kín lịch"
    ],
    [
        "date" => "2026-10-25",
        "start_time" => "08:00",
        "end_time" => "09:00",
        "trainer" => "Nguyễn Minh Anh",
        "specialty" => "Strength Training",
        "status" => "Còn trống"
    ]
];

function formatScheduleDate($date)
{
    return date("d/m/Y", strtotime($date));
}

// Lọc lịch theo trạng thái.
$filterStatus = $_GET["status"] ?? "all";

if (!in_array($filterStatus, ["all", "available", "booked"], true)) {
    $filterStatus = "all";
}

$filteredSchedules = array_filter(
    $ptSchedules,
    function ($schedule) use ($filterStatus) {
        if ($filterStatus === "available") {
            return $schedule["status"] === "Còn trống";
        }

        if ($filterStatus === "booked") {
            return $schedule["status"] === "Đã kín lịch";
        }

        return true;
    }
);

$totalSlots = count($ptSchedules);
$availableSlots = count(array_filter(
    $ptSchedules,
    fn($schedule) => $schedule["status"] === "Còn trống"
));
$bookedSlots = $totalSlots - $availableSlots;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= htmlspecialchars($pageTitle) ?> | FitZone</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
    <link href="../../assets/css/schedule.css" rel="stylesheet">
</head>

<body class="dashboard-page">
<div class="dashboard-layout">

    <!-- SIDEBAR -->
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

            <a href="../trainer/trainer.php" class="nav-item">
                <span class="nav-icon">♙</span>
                <span>Huấn luyện viên</span>
            </a>

            <a href="../trainer/booking.php" class="nav-item">
                <span class="nav-icon">▦</span>
                <span>Đặt lịch tập</span>
            </a>

            <a href="schedule.php" class="nav-item active">
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

    <!-- NỘI DUNG CHÍNH -->
    <div class="main-area">

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
                <h1>Lịch rảnh PT</h1>
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

            <!-- GIỚI THIỆU -->
            <section class="page-intro pt-schedule-intro">
                <p class="banner-label">PERSONAL TRAINER AVAILABILITY</p>
                <h2>Lịch rảnh của huấn luyện viên</h2>
                <p>
                    Xem các khung giờ PT còn trống và lựa chọn thời gian
                    phù hợp để đặt buổi tập.
                </p>
            </section>

            <!-- THỐNG KÊ -->
            <section class="pt-schedule-stats">
                <article class="pt-stat-card">
                    <p>Tổng khung giờ</p>
                    <h3><?= $totalSlots ?></h3>
                </article>

                <article class="pt-stat-card">
                    <p>Khung giờ còn trống</p>
                    <h3><?= $availableSlots ?></h3>
                </article>

                <article class="pt-stat-card">
                    <p>Khung giờ đã kín</p>
                    <h3><?= $bookedSlots ?></h3>
                </article>
            </section>

            <!-- DANH SÁCH LỊCH RẢNH -->
            <section class="section-block schedule-section">

                <div class="section-heading">
                    <div>
                        <h2>Các khung giờ của PT</h2>
                        <p>
                            Hiển thị <?= count($filteredSchedules) ?>
                            khung giờ
                        </p>
                    </div>
                </div>

                <!-- BỘ LỌC -->
                <div class="pt-filter">
                    <a
                        href="schedule.php?status=all"
                        class="<?= $filterStatus === 'all' ? 'active' : '' ?>"
                    >Tất cả</a>

                    <a
                        href="schedule.php?status=available"
                        class="<?= $filterStatus === 'available' ? 'active' : '' ?>"
                    >Còn trống</a>

                    <a
                        href="schedule.php?status=booked"
                        class="<?= $filterStatus === 'booked' ? 'active' : '' ?>"
                    >Đã kín lịch</a>
                </div>

                <!-- BẢNG DÀNH CHO MÁY TÍNH -->
                <div class="schedule-table-wrap">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Khung giờ</th>
                                <th>Huấn luyện viên</th>
                                <th>Chuyên môn</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($filteredSchedules)): ?>
                            <tr>
                                <td colspan="6" class="pt-empty-state">
                                    Không tìm thấy khung giờ phù hợp.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredSchedules as $schedule): ?>
                                <?php
                                $isAvailable =
                                    $schedule["status"] === "Còn trống";
                                ?>

                                <tr>
                                    <td>
                                        <?= formatScheduleDate($schedule["date"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($schedule["start_time"]) ?>
                                        -
                                        <?= htmlspecialchars($schedule["end_time"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($schedule["trainer"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($schedule["specialty"]) ?>
                                    </td>

                                    <td>
                                        <span class="pt-status <?= $isAvailable ? 'available' : 'booked' ?>">
                                            <?= htmlspecialchars($schedule["status"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ($isAvailable): ?>
                                            <a
                                                class="pt-booking-button"
                                                href="../trainer/booking.php"
                                            >Đặt lịch</a>
                                        <?php else: ?>
                                            <span class="pt-booking-button disabled">
                                                Đã kín
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- THẺ DÀNH CHO ĐIỆN THOẠI -->
                <div class="schedule-mobile-list">
                    <?php if (empty($filteredSchedules)): ?>
                        <div class="pt-empty-state">
                            Không tìm thấy khung giờ phù hợp.
                        </div>
                    <?php else: ?>
                        <?php foreach ($filteredSchedules as $schedule): ?>
                            <?php
                            $isAvailable =
                                $schedule["status"] === "Còn trống";
                            ?>

                            <article class="pt-mobile-card">
                                <div class="schedule-mobile-top">
                                    <strong>
                                        <?= formatScheduleDate($schedule["date"]) ?>
                                    </strong>

                                    <span class="pt-status <?= $isAvailable ? 'available' : 'booked' ?>">
                                        <?= htmlspecialchars($schedule["status"]) ?>
                                    </span>
                                </div>

                                <p>
                                    <strong>Khung giờ:</strong>
                                    <?= htmlspecialchars($schedule["start_time"]) ?>
                                    -
                                    <?= htmlspecialchars($schedule["end_time"]) ?>
                                </p>

                                <p>
                                    <strong>Huấn luyện viên:</strong>
                                    <?= htmlspecialchars($schedule["trainer"]) ?>
                                </p>

                                <p>
                                    <strong>Chuyên môn:</strong>
                                    <?= htmlspecialchars($schedule["specialty"]) ?>
                                </p>

                                <?php if ($isAvailable): ?>
                                    <a
                                        class="pt-booking-button"
                                        href="../trainer/booking.php"
                                    >Đặt lịch với PT</a>
                                <?php else: ?>
                                    <span class="pt-booking-button disabled">
                                        Khung giờ đã kín
                                    </span>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
