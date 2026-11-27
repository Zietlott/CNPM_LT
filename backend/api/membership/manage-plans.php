<?php
declare(strict_types=1);

require_once __DIR__ . '/../../controllers/MembershipController.php';
require_once __DIR__ . '/../../helpers/permissions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function membershipManagePlansRespond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isLoggedIn()) membershipManagePlansRespond(401, ['success' => false, 'message' => 'Bạn cần đăng nhập.']);
if (!hasStaffPermission('MEMBERSHIP_MANAGE')) membershipManagePlansRespond(403, ['success' => false, 'message' => 'Bạn không có quyền quản lý gói tập.']);

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$controller = new MembershipController();

if ($method === 'GET') {
    $result = $controller->getManagementPlans();
    membershipManagePlansRespond($result['success'] ? 200 : 500, $result);
}
if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
    header('Allow: GET, POST, PUT, PATCH');
    membershipManagePlansRespond(405, ['success' => false, 'message' => 'Phương thức không được hỗ trợ.']);
}
if (!verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    membershipManagePlansRespond(403, ['success' => false, 'message' => 'CSRF token không hợp lệ.']);
}
if (!preg_match('/^application\/json(?:\s*;|$)/i', $_SERVER['CONTENT_TYPE'] ?? '')) {
    membershipManagePlansRespond(415, ['success' => false, 'message' => 'Content-Type phải là application/json.']);
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) membershipManagePlansRespond(400, ['success' => false, 'message' => 'JSON không hợp lệ.']);

if ($method === 'PATCH' || ($method === 'POST' && ($input['action'] ?? '') === 'discontinue')) {
    $id = filter_var($input['plan_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) membershipManagePlansRespond(422, ['success' => false, 'message' => 'plan_id không hợp lệ.']);
    $result = $controller->discontinuePlan((int)$id);
} else {
    $id = null;
    if ($method === 'PUT') {
        $id = filter_var($input['plan_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || $id === null) membershipManagePlansRespond(422, ['success' => false, 'message' => 'plan_id không hợp lệ.']);
        $id = (int)$id;
    }
    $result = $controller->managePlans($input, $id);
}
membershipManagePlansRespond($result['success'] ? ($method === 'POST' ? 201 : 200) : 422, $result);
