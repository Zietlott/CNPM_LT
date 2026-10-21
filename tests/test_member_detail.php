<?php

require_once __DIR__ . '/../backend/services/AdminService.php';

try {
    $adminService = new AdminService();

    // Kiểm tra hội viên có ID = 2
    $member = $adminService->getMemberById(2);

    if ($member !== null) {
        echo "TEST 1: PASS - Tim thay hoi vien\n";
        echo "ID: " . $member['member_id'] . "\n";
        echo "Ho ten: " . $member['full_name'] . "\n";
        echo "Email: " . $member['email'] . "\n";
        echo "Trang thai: " . $member['status'] . "\n";
    } else {
        echo "TEST 1: FAIL - Khong tim thay hoi vien\n";
    }

    // Kiểm tra ID không tồn tại
    $notFound = $adminService->getMemberById(99999);

    echo "\nTEST 2: ";
    echo $notFound === null
        ? "PASS - ID khong ton tai\n"
        : "FAIL - ID khong ton tai nhung van tra ve du lieu\n";

    // Đảm bảo không trả về mật khẩu
    echo "TEST 3: ";
    echo $member !== null && !array_key_exists('password_hash', $member)
        ? "PASS - Khong lo mat khau\n"
        : "FAIL - Du lieu khong hop le\n";

} catch (Throwable $e) {
    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
}
