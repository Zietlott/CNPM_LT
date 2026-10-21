
<?php

require_once __DIR__ . '/../../controllers/AdminController.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chỉ Admin được phép khóa/mở khóa hội viên
if (!isAdmin()) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền truy cập.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// Kiểm tra CSRF token trước khi cập nhật dữ liệu
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'CSRF token không hợp lệ hoặc bị thiếu.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// Chỉ chấp nhận JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (!preg_match('/^application\/json(?:\s*;|$)/i', $contentType)) {
    http_response_code(415);

    echo json_encode([
        'success' => false,
        'message' => 'Content-Type phải là application/json.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Đọc dữ liệu JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu JSON không hợp lệ.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$memberId = filter_var(
    $input['member_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$status = $input['status'] ?? null;

// Kiểm tra dữ liệu đầu vào
if (
    $memberId === false ||
    !is_string($status) ||
    !in_array($status, ['active', 'locked'], true)
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Thông tin trạng thái không hợp lệ.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $controller = new AdminController();

    $result = $controller->updateMemberStatus(
        (int) $memberId,
        $status
    );

    http_response_code($result['success'] ? 200 : 400);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống.'
    ], JSON_UNESCAPED_UNICODE);
}