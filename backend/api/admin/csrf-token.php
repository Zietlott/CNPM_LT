<?php

require_once __DIR__ . '/../../helpers/auth.php';

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

// Chỉ Admin được lấy CSRF token
if (!isAdmin()) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền truy cập.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tránh cache token
header('Cache-Control: no-store');

http_response_code(200);

echo json_encode([
    'success' => true,
    'csrf_token' => getCsrfToken()
], JSON_UNESCAPED_UNICODE);
