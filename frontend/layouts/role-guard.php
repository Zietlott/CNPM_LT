<?php
/**
 * ROLE GUARD HELPER
 * FitZone - Fitness Center Membership & PT Booking App
 *
 * Cung cấp các hàm bảo vệ trang theo quyền (role-based access control).
 *
 * Cách dùng (đầu mỗi trang PHP):
 *   require_once __DIR__ . '/../../../backend/helpers/auth.php';
 *   require_once __DIR__ . '/../../layouts/role-guard.php';
 *
 *   // Chỉ cho phép member:
 *   requireRole('member');
 *
 *   // Cho phép admin hoặc staff:
 *   requireAnyRole(['admin', 'staff']);
 *
 *   // Chỉ kiểm tra JSON (API endpoint):
 *   requireRoleJson(['admin']);
 */

/* ============================================================
   requireLogin()
   Redirect về login nếu chưa đăng nhập.
   ============================================================ */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        _redirectLogin();
    }
}

/* ============================================================
   requireRole(string $role)
   Redirect nếu không phải role yêu cầu.
   ============================================================ */
function requireRole(string $role): void
{
    if (!isLoggedIn()) {
        _redirectLogin();
    }

    if (($_SESSION['role'] ?? '') !== $role) {
        _redirectByRole($_SESSION['role'] ?? '');
    }
}

/* ============================================================
   requireAnyRole(array $roles)
   Cho phép nếu role nằm trong danh sách.
   ============================================================ */
function requireAnyRole(array $roles): void
{
    if (!isLoggedIn()) {
        _redirectLogin();
    }

    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        _redirectByRole($_SESSION['role'] ?? '');
    }
}

/* ============================================================
   requireRoleJson(array $roles)
   Trả về JSON 401/403 nếu không đủ quyền (dùng cho API endpoint).
   ============================================================ */
function requireRoleJson(array $roles): void
{
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/* ============================================================
   canAccess(string $permission)
   Kiểm tra quyền bổ sung (staff permissions).
   ============================================================ */
function canAccess(string $permission): bool
{
    if (!isLoggedIn()) return false;

    $role = $_SESSION['role'] ?? '';

    // Admin luôn có toàn quyền
    if ($role === 'admin') return true;

    // Staff: kiểm tra quyền riêng được lưu trong session
    if ($role === 'staff') {
        return match($permission) {
            'booking.confirm' => (bool)($_SESSION['can_confirm'] ?? false),
            'booking.cancel'  => (bool)($_SESSION['can_cancel']  ?? false),
            default           => false,
        };
    }

    return false;
}

/* ============================================================
   renderRoleGuard(array $allowed, string $currentRole) - cho view
   Trả về true nếu được phép, để kiểm tra inline trong template.
   ============================================================ */
function roleIs(string ...$roles): bool
{
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['role'] ?? '', $roles, true);
}

/* ============================================================
   PRIVATE HELPERS
   ============================================================ */

function _redirectLogin(): never
{
    // Tính đường dẫn tương đối đến trang login từ file hiện tại
    // (fallback an toàn về gốc)
    header('Location: /frontend/pages/auth/login.php');
    exit;
}

function _redirectByRole(string $role): never
{
    $destination = match ($role) {
        'admin', 'staff' => '/frontend/pages/admin/bookings.php',
        'trainer'        => '/frontend/pages/trainer/pt-bookings.php',
        'member'         => '/frontend/pages/member/dashboard.php',
        default          => '/frontend/pages/auth/login.php',
    };
    header('Location: ' . $destination);
    exit;
}
