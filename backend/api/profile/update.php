<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';

function profileResponse(int $status, bool $success, string $message): void
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    profileResponse(405, false, 'Phương thức không được hỗ trợ.');
}

if (!isLoggedIn()) {
    profileResponse(401, false, 'Vui lòng đăng nhập.');
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!verifyCsrfToken($csrfToken)) {
    profileResponse(403, false, 'CSRF token không hợp lệ.');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    profileResponse(400, false, 'Dữ liệu JSON không hợp lệ.');
}

$fullName = $input['full_name'] ?? null;
$phone = $input['phone'] ?? null;

if (!is_string($fullName) || !is_string($phone)) {
    profileResponse(400, false, 'Họ tên và số điện thoại phải là chuỗi.');
}

$fullName = trim($fullName);
$phone = trim($phone);

if ($fullName === '' || $phone === '') {
    profileResponse(400, false, 'Vui lòng nhập đầy đủ thông tin.');
}

if (mb_strlen($fullName, 'UTF-8') > 100) {
    profileResponse(400, false, 'Họ tên không được quá 100 ký tự.');
}

if (
    strlen($phone) > 20 ||
    !preg_match('/^\+?[0-9]{9,15}$/', $phone)
) {
    profileResponse(400, false, 'Số điện thoại không hợp lệ.');
}

try {
    $user = currentUser();
    $pdo = getDBConnection();

    $stmt = $pdo->prepare(
        'UPDATE users
         SET full_name = ?, phone = ?
         WHERE id = ? AND status = ?'
    );

    $stmt->execute([
        $fullName,
        $phone,
        $user['member_id'],
        'active'
    ]);

    // Đồng bộ thông tin session sau khi cập nhật.
    $_SESSION['full_name'] = $fullName;

    profileResponse(200, true, 'Cập nhật thông tin cá nhân thành công.');

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        profileResponse(409, false, 'Số điện thoại đã được sử dụng.');
    }

    error_log('Profile update error: ' . $e->getMessage());
    profileResponse(500, false, 'Lỗi hệ thống.');

} catch (Throwable $e) {
    error_log('Profile update error: ' . $e->getMessage());
    profileResponse(500, false, 'Lỗi hệ thống.');
}
