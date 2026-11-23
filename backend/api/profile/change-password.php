<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../services/AuthService.php';

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, [
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ.'
    ]);
}

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    respond(401, [
        'success' => false,
        'message' => 'Vui lòng đăng nhập.'
    ]);
}

// Kiểm tra CSRF token
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!verifyCsrfToken($csrfToken)) {
    respond(403, [
        'success' => false,
        'message' => 'CSRF token không hợp lệ.'
    ]);
}

// Đọc dữ liệu JSON
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!is_array($data) || array_is_list($data)) {
    respond(400, [
        'success' => false,
        'message' => 'Dữ liệu JSON không hợp lệ.'
    ]);
}

$fields = [
    'current_password',
    'new_password',
    'confirm_password'
];

foreach ($fields as $field) {
    if (!isset($data[$field]) || !is_string($data[$field])) {
        respond(400, [
            'success' => false,
            'message' => 'Dữ liệu mật khẩu không hợp lệ.'
        ]);
    }
}

// Lấy ID từ session, không lấy từ JSON
$user = currentUser();
$userId = (int) ($user['member_id'] ?? 0);

if ($userId <= 0) {
    respond(401, [
        'success' => false,
        'message' => 'Phiên đăng nhập không hợp lệ.'
    ]);
}

try {
    $authService = new AuthService();

    $result = $authService->changePassword(
        $userId,
        $data['current_password'],
        $data['new_password'],
        $data['confirm_password']
    );

    respond($result['success'] ? 200 : 400, $result);

} catch (Throwable $e) {
    error_log('Change password API error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Có lỗi hệ thống, vui lòng thử lại.'
    ]);
}
