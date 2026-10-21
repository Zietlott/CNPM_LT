<?php

require_once __DIR__ . '/../../controllers/TrainerController.php';
require_once __DIR__ . '/../../helpers/auth.php';

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

// Chỉ Admin được cập nhật PT
if (!isAdmin()) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền truy cập.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra CSRF token
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'CSRF token không hợp lệ hoặc bị thiếu.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chỉ nhận JSON
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

// Kiểm tra dữ liệu đầu vào
$trainerId = filter_var(
    $input['trainer_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$fullName = $input['full_name'] ?? null;
$specialty = $input['specialty'] ?? null;

$experienceYears = filter_var(
    $input['experience_years'] ?? null,
    FILTER_VALIDATE_INT
);

$description = $input['description'] ?? null;

if (
    $trainerId === false ||
    $trainerId === null ||
    !is_string($fullName) ||
    !is_string($specialty) ||
    $experienceYears === false ||
    $experienceYears === null ||
    ($description !== null && !is_string($description))
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Thông tin huấn luyện viên không hợp lệ.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $controller = new TrainerController();

    $result = $controller->updateTrainer(
        (int) $trainerId,
        $fullName,
        $specialty,
        (int) $experienceYears,
        $description
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
