<?php
/**
 * API ĐÁNH DẤU THÔNG BÁO ĐÃ ĐỌC
 * FITZONE - FITNESS CENTER
 */

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập.',
        'errorCode' => 'UNAUTHENTICATED'
    ]);
    exit;
}

$userId = (int) ($_SESSION['member_id'] ?? 0);
$db = getDBConnection();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$notificationId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
$markAll = !empty($input['all']);

try {
    if ($markAll) {
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);
        $updated = $stmt->rowCount();
    } elseif ($notificationId) {
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);
        $updated = $stmt->rowCount();
    } else {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã đánh dấu thông báo đã đọc.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cập nhật: ' . $e->getMessage()
    ]);
}
