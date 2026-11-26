<?php

/**
 * FITZONE - Mark notifications as read
 * Method: POST
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/NotificationController.php';

function notificationReadResponse(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Only POST is allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    notificationReadResponse(405, [
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ.'
    ]);
}

// Authentication
if (!isLoggedIn()) {
    notificationReadResponse(401, [
        'success' => false,
        'message' => 'Vui lòng đăng nhập.',
        'errorCode' => 'UNAUTHENTICATED'
    ]);
}

$userId = (int) ($_SESSION['member_id'] ?? 0);

if ($userId <= 0) {
    notificationReadResponse(401, [
        'success' => false,
        'message' => 'Phiên đăng nhập không hợp lệ.'
    ]);
}

// CSRF protection
if (!verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    notificationReadResponse(403, [
        'success' => false,
        'message' => 'CSRF token không hợp lệ.'
    ]);
}

// Validate JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    notificationReadResponse(400, [
        'success' => false,
        'message' => 'Dữ liệu JSON không hợp lệ.'
    ]);
}

$markAll = ($input['all'] ?? false) === true;
$notificationId = filter_var(
    $input['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$markAll && (!$notificationId || $notificationId <= 0)) {
    notificationReadResponse(422, [
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ.'
    ]);
}

try {
    $controller = new NotificationController(getDBConnection());

    notificationReadResponse(
        200,
        $controller->markRead(
            $userId,
            $notificationId ?: null,
            $markAll
        )
    );
} catch (Throwable $e) {
    error_log('Notification read error: ' . $e->getMessage());

    notificationReadResponse(500, [
        'success' => false,
        'message' => 'Không thể cập nhật thông báo.'
    ]);
}
