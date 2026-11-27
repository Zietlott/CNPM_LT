<?php
declare(strict_types=1);

require_once __DIR__ . '/../../services/TrainerService.php';
require_once __DIR__ . '/../../helpers/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function trainerProfileRespond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isLoggedIn() || !isTrainer()) {
    trainerProfileRespond(403, ['success' => false, 'message' => 'Chỉ PT đang đăng nhập mới được truy cập hồ sơ này.']);
}

$service = new TrainerService();
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'GET') {
    $profile = $service->getOwnProfile((int)$_SESSION['member_id']);
    trainerProfileRespond($profile ? 200 : 404, [
        'success' => $profile !== null,
        'data' => $profile,
        'message' => $profile ? 'Lấy hồ sơ PT thành công.' : 'Không tìm thấy hồ sơ PT.'
    ]);
}
if ($method !== 'PUT') {
    header('Allow: GET, PUT');
    trainerProfileRespond(405, ['success' => false, 'message' => 'Phương thức không được hỗ trợ.']);
}
if (!verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    trainerProfileRespond(403, ['success' => false, 'message' => 'CSRF token không hợp lệ.']);
}
if (!preg_match('/^application\/json(?:\s*;|$)/i', $_SERVER['CONTENT_TYPE'] ?? '')) {
    trainerProfileRespond(415, ['success' => false, 'message' => 'Content-Type phải là application/json.']);
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) trainerProfileRespond(400, ['success' => false, 'message' => 'JSON không hợp lệ.']);

$fullName = $input['full_name'] ?? null;
$specialty = $input['specialty'] ?? null;
$experience = filter_var($input['experience_years'] ?? null, FILTER_VALIDATE_INT);
$bio = $input['description'] ?? null;
if (!is_string($fullName) || !is_string($specialty) || $experience === false
    || ($bio !== null && !is_string($bio))) {
    trainerProfileRespond(422, ['success' => false, 'message' => 'Thông tin hồ sơ PT không hợp lệ.']);
}
try {
    $service->updateOwnProfile((int)$_SESSION['member_id'], $fullName, $specialty, (int)$experience, $bio);
    $_SESSION['full_name'] = trim($fullName);
    trainerProfileRespond(200, ['success' => true, 'message' => 'Cập nhật hồ sơ PT thành công.']);
} catch (InvalidArgumentException $error) {
    trainerProfileRespond(422, ['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('Trainer profile update failed: ' . $error->getMessage());
    trainerProfileRespond(500, ['success' => false, 'message' => 'Không thể cập nhật hồ sơ PT lúc này.']);
}
