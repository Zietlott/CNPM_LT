
<?php

require_once __DIR__ . '/../backend/services/AdminService.php';

$db = null;

try {
    // Tạo một kết nối PDO dùng chung
    $db = getDBConnection();
    $service = new AdminService($db);

    // Bắt đầu Transaction
    $db->beginTransaction();

    // Lấy thông tin hội viên ID = 2
    $memberId = 2;
    $original = $service->getMemberById($memberId);

    if ($original === null) {
        throw new RuntimeException('Khong tim thay hoi vien test');
    }

    // Cập nhật thử thông tin
    $updated = $service->updateMember(
        $memberId,
        'Nguyen Van Test Update',
        '0999999888'
    );

    $after = $service->getMemberById($memberId);

    echo "TEST 1: ";
    echo $updated
        && $after !== null
        && $after['full_name'] === 'Nguyen Van Test Update'
        && $after['phone'] === '0999999888'
        ? "PASS - Cap nhat thanh cong\n"
        : "FAIL - Cap nhat khong thanh cong\n";

    // Hoàn tác mọi thay đổi trong Transaction
    $db->rollBack();

    // Kiểm tra dữ liệu sau ROLLBACK
    $restored = $service->getMemberById($memberId);

    echo "TEST 2: ";
    echo $restored !== null
        && $restored['full_name'] === $original['full_name']
        && $restored['phone'] === $original['phone']
        ? "PASS - ROLLBACK thanh cong\n"
        : "FAIL - Du lieu chua duoc khoi phuc\n";

} catch (Throwable $e) {

    // Nếu xảy ra lỗi thì vẫn hoàn tác
    if ($db !== null && $db->inTransaction()) {
        $db->rollBack();
    }

    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
}
