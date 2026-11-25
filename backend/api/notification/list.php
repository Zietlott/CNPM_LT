<?php
/**
 * API LẤY DANH SÁCH THÔNG BÁO CHO TÀI KHOẢN ĐANG ĐĂNG NHẬP
 * FITZONE - FITNESS CENTER
 */

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để xem thông báo.',
        'errorCode' => 'UNAUTHENTICATED'
    ]);
    exit;
}

$userId = (int) ($_SESSION['member_id'] ?? 0);
$db = getDBConnection();

try {
    // Đếm số thông báo chưa đọc
    $stmtCount = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
    $stmtCount->execute(['user_id' => $userId]);
    $unreadCount = (int) $stmtCount->fetchColumn();

    // Lấy 30 thông báo gần nhất
    $stmt = $db->prepare(
        'SELECT id, user_id, type, title, content, reference_id, is_read, created_at
         FROM notifications
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT 30'
    );
    $stmt->execute(['user_id' => $userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => $notifications,
            'unread_count'  => $unreadCount
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi tải thông báo: ' . $e->getMessage()
    ]);
}
