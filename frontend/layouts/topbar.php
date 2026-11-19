<?php
/**
 * SHARED TOPBAR LAYOUT COMPONENT
 * FitZone - Fitness Center Membership & PT Booking App
 *
 * Biến đầu vào (định nghĩa trước khi include):
 *   $topbarLabel    - label nhỏ phía trên tiêu đề, ví dụ: 'MEMBER AREA'
 *   $topbarTitle    - tiêu đề trang, ví dụ: 'Trang chủ'
 *   $sidebarRole    - 'member' | 'trainer' | 'admin' | 'staff'
 */

$topbarLabel = $topbarLabel ?? 'FITZONE';
$topbarTitle = $topbarTitle ?? 'Dashboard';
$sidebarRole = $sidebarRole ?? ($_SESSION['role'] ?? 'member');

$roleLabels = [
    'member'  => 'Hội viên',
    'trainer' => 'Huấn luyện viên',
    'admin'   => 'Quản trị viên',
    'staff'   => 'Nhân viên',
];

$avatarInitials = [
    'member'  => 'HV',
    'trainer' => 'PT',
    'admin'   => 'AD',
    'staff'   => 'ST',
];

$userRoleLabel = $roleLabels[$sidebarRole]   ?? 'Người dùng';
$avatarText    = $avatarInitials[$sidebarRole] ?? 'FZ';
$displayName   = $_SESSION['full_name'] ?? $userRoleLabel;
?>

<header class="topbar">

    <button
        type="button"
        class="menu-toggle"
        id="menuToggle"
        aria-label="Mở menu điều hướng"
        aria-controls="sidebar"
        aria-expanded="false"
    >☰</button>

    <div class="topbar-title">
        <span class="topbar-label"><?= htmlspecialchars($topbarLabel, ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars($topbarTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    </div>

    <div class="user-info" aria-label="Thông tin người dùng">
        <div class="user-avatar" aria-hidden="true"><?= htmlspecialchars($avatarText, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="user-text">
            <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars($userRoleLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

</header>
