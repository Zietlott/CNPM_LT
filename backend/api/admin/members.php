<?php

require_once __DIR__ . '/../../controllers/AdminController.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép phương thức GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Chỉ Admin được xem danh sách hội viên
if (!isAdmin()) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền truy cập.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $controller = new AdminController();
    $result = $controller->getMembers();

    http_response_code($result['success'] ? 200 : 403);

    echo json_encode(
        $result,
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống.'
    ], JSON_UNESCAPED_UNICODE);
}
