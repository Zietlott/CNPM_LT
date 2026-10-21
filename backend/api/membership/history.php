<?php

require_once __DIR__ . '/../../controllers/MembershipController.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Kiểm tra quyền hội viên
if (!isMember()) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Can dang nhap bang tai khoan hoi vien'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Lấy lịch sử đăng ký của hội viên đang đăng nhập
$controller = new MembershipController();
$result = $controller->getMyHistory();

http_response_code($result['success'] ? 200 : 500);

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
);
