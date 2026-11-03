<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ.'
    ]);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập.'
    ]);
    exit;
}

try {
    $user = currentUser();

    $pdo = getDBConnection();

    $stmt = $pdo->prepare(
        'SELECT member_id, full_name, email, phone,
                role, status, created_at
         FROM member
         WHERE member_id = ? AND status = ?
         LIMIT 1'
    );

    $stmt->execute([
        $user['member_id'],
        'active'
    ]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy tài khoản.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $profile
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Profile API error: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống.'
    ], JSON_UNESCAPED_UNICODE);
}
