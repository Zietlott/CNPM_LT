<?php

require_once __DIR__ . '/../backend/controllers/AdminController.php';

try {
    $controller = new AdminController();

    // TEST 1: Chưa đăng nhập
    $_SESSION = [];

    $result = $controller->getMemberDetail(2);

    echo "TEST 1: ";
    echo $result['success'] === false
        ? "PASS - Chua dang nhap bi chan\n"
        : "FAIL - Chua dang nhap van truy cap duoc\n";

    // TEST 2: Tài khoản Member
    $_SESSION['member_id'] = 2;
    $_SESSION['role'] = 'member';

    $result = $controller->getMemberDetail(2);

    echo "TEST 2: ";
    echo $result['success'] === false
        ? "PASS - Member bi chan\n"
        : "FAIL - Member van truy cap duoc\n";

    // TEST 3: Tài khoản Admin
    $_SESSION['member_id'] = 1;
    $_SESSION['role'] = 'admin';

    $result = $controller->getMemberDetail(2);

    echo "TEST 3: ";
    echo $result['success'] === true
        && isset($result['data'])
        && $result['data']['member_id'] == 2
        ? "PASS - Admin xem duoc chi tiet\n"
        : "FAIL - Admin khong xem duoc chi tiet\n";

    // TEST 4: ID không tồn tại
    $result = $controller->getMemberDetail(99999);

    echo "TEST 4: ";
    echo $result['success'] === false
        ? "PASS - ID khong ton tai bi tu choi\n"
        : "FAIL - ID khong ton tai\n";

    // Dọn Session giả lập
    $_SESSION = [];

} catch (Throwable $e) {
    $_SESSION = [];
    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
}
