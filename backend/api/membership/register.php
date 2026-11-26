<?php

declare(strict_types=1);

require_once __DIR__ . '/../../controllers/MembershipController.php';
require_once __DIR__ . '/../../helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function membershipRegisterResponse(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    membershipRegisterResponse(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

if (!isLoggedIn() || !isMember()) {
    membershipRegisterResponse(401, [
        'success' => false,
        'message' => 'Can dang nhap bang tai khoan hoi vien'
    ]);
}

if (!preg_match(
    '/^application\/json(?:\s*;|$)/i',
    $_SERVER['CONTENT_TYPE'] ?? ''
)) {
    membershipRegisterResponse(415, [
        'success' => false,
        'message' => 'Content-Type phai la application/json'
    ]);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!verifyCsrfToken($token)) {
    membershipRegisterResponse(403, [
        'success' => false,
        'message' => 'CSRF token khong hop le'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

if (
    !is_array($input) ||
    !isset($input['plan_id']) ||
    filter_var(
        $input['plan_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) === false
) {
    membershipRegisterResponse(400, [
        'success' => false,
        'message' => 'plan_id khong hop le'
    ]);
}

$controller = new MembershipController();
$result = $controller->registerPlan((int)$input['plan_id']);

if ($result['success']) {
    membershipRegisterResponse(201, $result);
}

membershipRegisterResponse(400, $result);
