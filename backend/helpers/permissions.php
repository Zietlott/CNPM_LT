<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Admin có toàn quyền; Staff chỉ được phép khi có permission tương ứng.
 */
function hasStaffPermission(string $permission, ?int $userId = null): bool
{
    if (!isLoggedIn()) {
        return false;
    }

    if (isAdmin()) {
        return true;
    }

    if (!isStaff()) {
        return false;
    }

    $userId = $userId ?? (int)($_SESSION['member_id'] ?? 0);
    if ($userId <= 0 || !preg_match('/^[A-Z][A-Z0-9_]{1,49}$/', $permission)) {
        return false;
    }

    try {
        $stmt = getDBConnection()->prepare(
            'SELECT 1
             FROM staff_permissions
             WHERE staff_id = :staff_id AND permission = :permission
             LIMIT 1'
        );
        $stmt->execute([
            'staff_id' => $userId,
            'permission' => $permission
        ]);
        return $stmt->fetchColumn() !== false;
    } catch (Throwable $error) {
        error_log('Permission lookup failed: ' . $error->getMessage());
        return false;
    }
}
