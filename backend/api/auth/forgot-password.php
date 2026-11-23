<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../services/AuthService.php';

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// AUTH-06: Chỉ chấp nhận POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ.'
    ]);
}

// Giới hạn kích thước request để tránh dữ liệu quá lớn.
if (
    isset($_SERVER['CONTENT_LENGTH']) &&
    (int) $_SERVER['CONTENT_LENGTH'] > 4096
) {
    respond(413, [
        'success' => false,
        'message' => 'Dữ liệu gửi lên quá lớn.'
    ]);
}

// Chỉ nhận JSON.
$contentType = strtolower(trim(
    explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]
));

if ($contentType !== 'application/json') {
    respond(415, [
        'success' => false,
        'message' => 'Vui lòng gửi dữ liệu dạng JSON.'
    ]);
}

$rawBody = file_get_contents('php://input');

if ($rawBody === false || strlen($rawBody) > 4096) {
    respond(413, [
        'success' => false,
        'message' => 'Dữ liệu gửi lên không hợp lệ hoặc quá lớn.'
    ]);
}

$data = json_decode($rawBody, true);

if (!is_array($data) || array_is_list($data)) {
    respond(400, [
        'success' => false,
        'message' => 'Dữ liệu JSON không hợp lệ.'
    ]);
}

if (
    !isset($data['email']) ||
    !is_string($data['email'])
) {
    respond(400, [
        'success' => false,
        'message' => 'Email không hợp lệ.'
    ]);
}

try {
    $authService = new AuthService();

    $result = $authService->requestPasswordReset(
        $data['email']
    );

    respond($result['success'] ? 200 : 400, $result);

} catch (Throwable $e) {
    error_log(
        'Forgot password API error: ' . get_class($e)
    );

    respond(500, [
        'success' => false,
        'message' => 'Có lỗi hệ thống, vui lòng thử lại.'
    ]);
}
