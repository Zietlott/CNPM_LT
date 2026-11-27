<?php
declare(strict_types=1);
require_once __DIR__ . '/../../controllers/MembershipController.php';
require_once __DIR__ . '/../../helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function membershipCancelRespond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    membershipCancelRespond(405, ['success' => false, 'message' => 'Method not allowed']);
}
if (!isLoggedIn() || !isMember()) membershipCancelRespond(401, ['success' => false, 'message' => 'Bạn cần đăng nhập bằng tài khoản hội viên.']);
if (!verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) membershipCancelRespond(403, ['success' => false, 'message' => 'CSRF token không hợp lệ.']);
if (!preg_match('/^application\/json(?:\s*;|$)/i', $_SERVER['CONTENT_TYPE'] ?? '')) membershipCancelRespond(415, ['success' => false, 'message' => 'Content-Type phải là application/json.']);
$input = json_decode(file_get_contents('php://input'), true);
$id = is_array($input) ? filter_var($input['membership_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$reason = is_array($input) ? ($input['reason'] ?? null) : null;
if ($id === false || $id === null || ($reason !== null && !is_string($reason))) membershipCancelRespond(422, ['success' => false, 'message' => 'Dữ liệu hủy Membership không hợp lệ.']);
$result = (new MembershipController())->cancelMyMembership((int)$id, $reason);
membershipCancelRespond($result['success'] ? 200 : 422, $result);
