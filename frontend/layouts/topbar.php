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

    <!-- Notification Dropdown -->
    <div class="topbar-actions" style="display:flex;align-items:center;gap:12px;margin-left:auto;margin-right:16px;position:relative;">
        <button type="button" class="btn-notification" id="btnNotification" title="Thông báo" aria-label="Thông báo" style="position:relative;background:#f8fafc;border:1px solid #e2e8f0;cursor:pointer;font-size:1.15rem;padding:7px 11px;border-radius:10px;color:#334155;display:flex;align-items:center;justify-content:center;transition:all 0.15s ease;">
            🔔
            <span id="notifBadge" style="display:none;position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:2px 5px;border-radius:10px;line-height:1;box-shadow:0 0 0 2px #fff;">0</span>
        </button>
        <div id="notifDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:340px;max-height:420px;overflow-y:auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 12px 30px -4px rgba(0,0,0,0.12), 0 6px 12px -4px rgba(0,0,0,0.06);z-index:1050;padding:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #f1f5f9;">
                <span style="font-weight:700;font-size:0.95rem;color:#0f172a;">Thông báo của bạn</span>
                <button type="button" id="btnMarkAllRead" style="background:none;border:none;color:#16a34a;font-size:0.8rem;font-weight:600;cursor:pointer;padding:0;">Đã đọc tất cả</button>
            </div>
            <div id="notifList" style="display:flex;flex-direction:column;gap:8px;">
                <p style="text-align:center;color:#94a3b8;font-size:0.85rem;margin:16px 0;">Đang tải...</p>
            </div>
        </div>
    </div>
    <script src="../../assets/js/notifications.js" defer></script>

    <div class="user-info" aria-label="Thông tin người dùng">
        <div class="user-avatar" aria-hidden="true"><?= htmlspecialchars($avatarText, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="user-text">
            <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars($userRoleLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

</header>
