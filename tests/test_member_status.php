<?php

require_once __DIR__ . '/../backend/services/AdminService.php';

$db = null;

try {
    // Tạo kết nối PDO dùng chung
    $db = getDBConnection();
    $service = new AdminService($db);

    // Bắt đầu Transaction
    $db->beginTransaction();

    // Hội viên ID = 2 (Nguyễn Văn An)
    $memberId = 2;
    $original = $service->getMemberById($memberId);

    if ($original === null) {
        throw new RuntimeException('Khong tim thay hoi vien test');
    }

    // TEST 1: Khóa tài khoản
    $service->updateMemberStatus($memberId, 'locked');
    $locked = $service->getMemberById($memberId);

    echo "TEST 1: ";
    echo $locked !== null && $locked['status'] === 'locked'
        ? "PASS - Khoa tai khoan thanh cong\n"
        : "FAIL - Khoa tai khoan\n";

    // TEST 2: Mở khóa tài khoản
    $service->updateMemberStatus($memberId, 'active');
    $active = $service->getMemberById($memberId);

    echo "TEST 2: ";
    echo $active !== null && $active['status'] === 'active'
        ? "PASS - Mo khoa tai khoan thanh cong\n"
        : "FAIL - Mo khoa tai khoan\n";

    // TEST 3: Từ chối trạng thái không hợp lệ
    $invalidRejected = false;

    try {
        $service->updateMemberStatus($memberId, 'deleted');
    } catch (InvalidArgumentException $e) {
        $invalidRejected = true;
    }

    echo "TEST 3: ";
    echo $invalidRejected
        ? "PASS - Tu choi trang thai khong hop le\n"
        : "FAIL - Chap nhan trang thai khong hop le\n";

    // Hoàn tác thay đổi
    $db->rollBack();

    // TEST 4: Kiểm tra dữ liệu đã khôi phục
    $restored = $service->getMemberById($memberId);

    echo "TEST 4: ";
    echo $restored !== null
        && $restored['status'] === $original['status']
        ? "PASS - ROLLBACK thanh cong\n"
        : "FAIL - ROLLBACK\n";

} catch (Throwable $e) {
    if ($db !== null && $db->inTransaction()) {
        $db->rollBack();
    }

    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
}
