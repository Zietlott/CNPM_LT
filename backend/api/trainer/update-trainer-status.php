<?php

require_once __DIR__ . '/../../controllers/TrainerController.php';
require_once __DIR__ . '/../../helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền truy cập.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'CSRF token không hợp lệ hoặc bị thiếu.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (!preg_match('/^application\/json(?:\s*;|$)/i', $contentType)) {
    http_response_code(415);
    echo json_encode([
        'success' => false,
        'message' => 'Content-Type phải là application/json.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu JSON không hợp lệ.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$trainerId = filter_var(
    $input['trainer_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$isActive = $input['is_active'] ?? null;

if (
    $trainerId === false ||
    $trainerId === null ||
    !in_array($isActive, [0, 1], true)
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID hoặc trạng thái PT không hợp lệ.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $controller = new TrainerController();

    $result = $controller->updateTrainerStatus(
        (int) $trainerId,
        (bool) $isActive
    );

    http_response_code($result['success'] ? 200 : 404);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống.'
    ], JSON_UNESCAPED_UNICODE);
}
