<?php

require_once __DIR__ . '/../../controllers/AdminController.php';

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

// Chỉ Admin được truy cập
if (!isAdmin()) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền truy cập.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra member_id
$memberId = filter_input(
    INPUT_GET,
    'member_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($memberId === false || $memberId === null) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'ID hội viên không hợp lệ.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $controller = new AdminController();
    $result = $controller->getMemberDetail($memberId);

    http_response_code(
        $result['success'] ? 200 : 404
    );

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
