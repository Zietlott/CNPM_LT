<?php

require_once __DIR__ . '/../backend/controllers/AdminController.php';

$db = null;

try {
    $db = getDBConnection();
    $db->beginTransaction();

    // Dùng cùng kết nối PDO để có thể ROLLBACK
    $service = new AdminService($db);
    $controller = new AdminController();

    // Gắn service dùng chung kết nối vào controller
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

    // TEST 1: Member không được cập nhật
    $_SESSION = [
        'member_id' => 2,
        'role' => 'member'
    ];

    $result = $controller->updateMember(
        $memberId,
        'Test Member',
        '0999999888'
    );

    echo "TEST 1: ";
    echo $result['success'] === false
        ? "PASS - Member bi chan\n"
        : "FAIL - Member duoc cap nhat\n";

    // TEST 2: Admin được cập nhật
    $_SESSION = [
        'member_id' => 1,
        'role' => 'admin'
    ];

    $result = $controller->updateMember(
        $memberId,
        'Test Admin Update',
        '0999999888'
    );

    $after = $service->getMemberById($memberId);

    echo "TEST 2: ";
    echo $result['success'] === true
        && $after !== null
        && $after['full_name'] === 'Test Admin Update'
        && $after['phone'] === '0999999888'
        ? "PASS - Admin cap nhat thanh cong\n"
        : "FAIL - Admin cap nhat khong thanh cong\n";

    // Hoàn tác dữ liệu thử nghiệm
    $db->rollBack();

    $restored = $service->getMemberById($memberId);

    echo "TEST 3: ";
    echo $restored !== null
        && $restored['full_name'] === $original['full_name']
        && $restored['phone'] === $original['phone']
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

