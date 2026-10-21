<?php

require_once __DIR__ . '/../../controllers/MembershipController.php';

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

// Chỉ nhận JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (!preg_match('/^application\/json(?:\s*;|$)/i', $contentType)) {
    http_response_code(415);

    echo json_encode([
        'success' => false,
        'message' => 'Content-Type phai la application/json'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)
    || !isset($input['plan_id'])
    || filter_var(
        $input['plan_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) === false
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'plan_id khong hop le'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if (!isMember()) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Can dang nhap bang tai khoan hoi vien'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$controller = new MembershipController();

$result = $controller->registerPlan((int) $input['plan_id']);

http_response_code($result['success'] ? 201 : 400);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
