<?php

/**
 * API lấy danh sách thông báo của người dùng.
 * Method: GET
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/NotificationController.php';

function notificationListResponse(int $status, array $data): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Chỉ cho phép GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');

    notificationListResponse(405, [
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ.'
    ]);
}

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    notificationListResponse(401, [
        'success' => false,
        'message' => 'Vui lòng đăng nhập để xem thông báo.',
        'errorCode' => 'UNAUTHENTICATED'
    ]);
}

$userId = (int) ($_SESSION['member_id'] ?? 0);

if ($userId <= 0) {
    notificationListResponse(401, [
        'success' => false,
        'message' => 'Phiên đăng nhập không hợp lệ.'
    ]);
}

try {
    $db = getDBConnection();

    $controller = new NotificationController($db);

    notificationListResponse(
        200,
        $controller->list($userId)
    );

} catch (Throwable $e) {
    error_log(
        'Notification list error: ' . $e->getMessage()
    );

    notificationListResponse(500, [
        'success' => false,
        'message' => 'Không thể tải danh sách thông báo.'
    ]);
}
