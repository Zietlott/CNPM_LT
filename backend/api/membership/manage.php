<?php
declare(strict_types=1);
require_once __DIR__ . '/../../controllers/MembershipController.php';
require_once __DIR__ . '/../../helpers/permissions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function membershipManageRespond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isLoggedIn()) membershipManageRespond(401, ['success' => false, 'message' => 'Bạn cần đăng nhập.']);
if (!hasStaffPermission('MEMBERSHIP_MANAGE')) membershipManageRespond(403, ['success' => false, 'message' => 'Bạn không có quyền quản lý Membership.']);

$controller = new MembershipController();
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'GET') {
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? (string)$_GET['status'] : null;
    $memberId = null;
    if (isset($_GET['member_id']) && $_GET['member_id'] !== '') {
        $memberId = filter_var($_GET['member_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($memberId === false) membershipManageRespond(422, ['success' => false, 'message' => 'member_id không hợp lệ.']);
    }
    $result = $controller->getManagedMemberships($status, $memberId === null ? null : (int)$memberId);
    membershipManageRespond($result['success'] ? 200 : 422, $result);
}
if ($method !== 'POST') {
    header('Allow: GET, POST');
    membershipManageRespond(405, ['success' => false, 'message' => 'Phương thức không được hỗ trợ.']);
}
if (!verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) membershipManageRespond(403, ['success' => false, 'message' => 'CSRF token không hợp lệ.']);
if (!preg_match('/^application\/json(?:\s*;|$)/i', $_SERVER['CONTENT_TYPE'] ?? '')) membershipManageRespond(415, ['success' => false, 'message' => 'Content-Type phải là application/json.']);
$input = json_decode(file_get_contents('php://input'), true);
$id = is_array($input) ? filter_var($input['membership_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$reason = is_array($input) ? ($input['reason'] ?? null) : null;
if ($id === false || $id === null || ($reason !== null && !is_string($reason))) membershipManageRespond(422, ['success' => false, 'message' => 'Dữ liệu quản lý Membership không hợp lệ.']);
$result = $controller->cancelManagedMembership((int)$id, $reason);
membershipManageRespond($result['success'] ? 200 : 422, $result);
