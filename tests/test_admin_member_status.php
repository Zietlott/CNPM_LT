<?php

require_once __DIR__ . '/../backend/controllers/AdminController.php';

$db = null;

try {
    $db = getDBConnection();
    $db->beginTransaction();

    $service = new AdminService($db);
    $controller = new AdminController();

    // Dùng chung kết nối PDO để có thể ROLLBACK
    $reflection = new ReflectionProperty(
        AdminController::class,
        'service'
    );
    $reflection->setAccessible(true);
    $reflection->setValue($controller, $service);

    $memberId = 2;
    $original = $service->getMemberById($memberId);

    if ($original === null) {
        throw new RuntimeException('Khong tim thay hoi vien test');
    }

    // TEST 1: Member không được khóa tài khoản
    $_SESSION = [
        'member_id' => 2,
        'role' => 'member'
    ];

    $result = $controller->updateMemberStatus(
        $memberId,
        'locked'
    );

    $afterMember = $service->getMemberById($memberId);

    echo "TEST 1: ";
    echo $result['success'] === false
        && $afterMember['status'] === $original['status']
        ? "PASS - Member bi chan\n"
        : "FAIL - Member van thay doi duoc trang thai\n";

    // TEST 2: Admin được khóa tài khoản
    $_SESSION = [
        'member_id' => 1,
        'role' => 'admin'
    ];

    $result = $controller->updateMemberStatus(
        $memberId,
        'locked'
    );

    $locked = $service->getMemberById($memberId);

    echo "TEST 2: ";
    echo $result['success'] === true
        && $locked['status'] === 'locked'
        ? "PASS - Admin khoa thanh cong\n"
        : "FAIL - Admin khong khoa duoc\n";

    // TEST 3: Admin được mở khóa tài khoản
    $result = $controller->updateMemberStatus(
        $memberId,
        'active'
    );

    $active = $service->getMemberById($memberId);

    echo "TEST 3: ";
    echo $result['success'] === true
        && $active['status'] === 'active'
        ? "PASS - Admin mo khoa thanh cong\n"
        : "FAIL - Admin khong mo khoa duoc\n";

    // TEST 4: Từ chối trạng thái không hợp lệ
    $result = $controller->updateMemberStatus(
        $memberId,
        'deleted'
    );

    echo "TEST 4: ";
    echo $result['success'] === false
        ? "PASS - Tu choi trang thai sai\n"
        : "FAIL - Chap nhan trang thai sai\n";

    // Hoàn tác dữ liệu thử nghiệm
    $db->rollBack();

    $restored = $service->getMemberById($memberId);

    echo "TEST 5: ";
    echo $restored !== null
        && $restored['status'] === $original['status']
        ? "PASS - ROLLBACK thanh cong\n"
        : "FAIL - ROLLBACK that bai\n";

    $_SESSION = [];

} catch (Throwable $e) {
    if ($db !== null && $db->inTransaction()) {
        $db->rollBack();
    }

    $_SESSION = [];

    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
}
