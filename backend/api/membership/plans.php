<?php

require_once __DIR__ . '/../../controllers/MembershipController.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);

    exit;
}

$controller = new MembershipController();
$result = $controller->getPlans();

http_response_code($result['success'] ? 200 : 500);

echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE
);
