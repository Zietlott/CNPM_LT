<?php
/**
 * SHARED SIDEBAR LAYOUT COMPONENT
 * FitZone - Fitness Center Membership & PT Booking App
 *
 * Cách dùng: include sidebar.php từ bất kỳ trang dashboard nào.
 *
 * Biến đầu vào (định nghĩa trước khi include):
 *   $sidebarRole   - 'member' | 'trainer' | 'admin' | 'staff'   (mặc định: 'member')
 *   $activeNav     - key của item đang active, ví dụ: 'dashboard', 'membership', ...
 *   $csrfToken     - CSRF token để gắn vào form logout
 *   $brandHref     - href logo, mặc định tuỳ theo role
 */

$sidebarRole  = $sidebarRole  ?? ($_SESSION['role'] ?? 'member');
$activeNav    = $activeNav    ?? '';
$csrfToken    = $csrfToken    ?? getCsrfToken();

// ---- Cấu hình menu theo role ----

$menus = [
    'member' => [
        'label' => 'MENU CHÍNH',
        'brand' => ['href' => '../member/dashboard.php', 'label' => 'MEMBER AREA'],
        'items' => [
            ['key' => 'dashboard',   'icon' => '⌂',  'label' => 'Trang chủ',          'href' => '../member/dashboard.php'],
            ['key' => 'membership',  'icon' => '▣',  'label' => 'Gói hội viên',        'href' => '../member/membership.php'],
            ['key' => 'trainers',    'icon' => '♙',  'label' => 'Huấn luyện viên',     'href' => '../trainer/trainer.php'],
            ['key' => 'booking',     'icon' => '▦',  'label' => 'Đặt lịch tập',        'href' => '../trainer/booking.php'],
            ['key' => 'schedule',    'icon' => '◷',  'label' => 'Lịch PT',             'href' => '../trainer/schedule.php'],
            ['key' => 'profile',     'icon' => '⚙',  'label' => 'Thông tin cá nhân',   'href' => '../trainer/profile.php'],
        ],
        'logout_href' => '../auth/logout.php',
    ],
    'trainer' => [
        'label' => 'HUẤN LUYỆN VIÊN',
        'brand' => ['href' => '../trainer/pt-bookings.php', 'label' => 'TRAINER AREA'],
        'items' => [
            ['key' => 'pt-bookings', 'icon' => '▦',  'label' => 'Booking & lịch làm việc', 'href' => '../trainer/pt-bookings.php'],
            ['key' => 'profile',     'icon' => '⚙',  'label' => 'Thông tin cá nhân',       'href' => '../trainer/profile.php'],
        ],
        'logout_href' => '../auth/logout.php',
    ],
    'admin' => [
        'label' => 'QUẢN TRỊ',
        'brand' => ['href' => '../admin/bookings.php', 'label' => 'ADMIN AREA'],
        'items' => [
            ['key' => 'bookings',    'icon' => '▦',  'label' => 'Quản lý booking',     'href' => '../admin/bookings.php'],
            ['key' => 'members',     'icon' => '👤', 'label' => 'Quản lý hội viên',    'href' => '../admin/members.php'],
            ['key' => 'trainers-ad', 'icon' => '♙',  'label' => 'Quản lý PT',          'href' => '../admin/trainers.php'],
            ['key' => 'profile',     'icon' => '⚙',  'label' => 'Thông tin cá nhân',   'href' => '../trainer/profile.php'],
        ],
        'logout_href' => '../auth/logout.php',
    ],
    'staff' => [
        'label' => 'NHÂN VIÊN',
        'brand' => ['href' => '../admin/bookings.php', 'label' => 'STAFF AREA'],
        'items' => [
            ['key' => 'bookings',    'icon' => '▦',  'label' => 'Quản lý booking',     'href' => '../admin/bookings.php'],
            ['key' => 'profile',     'icon' => '⚙',  'label' => 'Thông tin cá nhân',   'href' => '../trainer/profile.php'],
        ],
        'logout_href' => '../auth/logout.php',
    ],
];

$menu       = $menus[$sidebarRole] ?? $menus['member'];
$brandHref  = $brandHref ?? $menu['brand']['href'];

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>

<aside class="sidebar" id="sidebar" aria-label="Menu điều hướng">

    <a href="<?= e($brandHref) ?>" class="brand" aria-label="FitZone - Trang chủ">
        <span class="brand-mark" aria-hidden="true">F</span>
        <span>FITZONE</span>
    </a>

    <p class="menu-label"><?= e($menu['label']) ?></p>

    <nav class="sidebar-nav" aria-label="Điều hướng chính">
        <?php foreach ($menu['items'] as $item): ?>
            <a href="<?= e($item['href']) ?>"
               class="nav-item<?= ($activeNav === $item['key']) ? ' active' : '' ?>"
               <?= ($activeNav === $item['key']) ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon" aria-hidden="true"><?= $item['icon'] ?></span>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
        <form action="<?= e($menu['logout_href']) ?>" method="POST" style="margin:0">
            <input type="hidden" name="csrf_token"
                   value="<?= e($csrfToken) ?>">
            <button type="submit" class="nav-item logout-link"
                    style="width:100%;border:none;background:transparent;text-align:left;cursor:pointer;font:inherit;">
                <span class="nav-icon" aria-hidden="true">↪</span>
                <span>Đăng xuất</span>
            </button>
        </form>
    </div>

</aside>
