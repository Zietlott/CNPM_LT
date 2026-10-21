<?php

require_once __DIR__ . '/../backend/services/AdminService.php';

try {
    $adminService = new AdminService();

    $members = $adminService->getAllMembers();

    echo "Ket noi va truy van thanh cong!\n";
    echo "Tong so hoi vien: " . count($members) . "\n\n";

    foreach ($members as $member) {
        echo "ID: " . $member['member_id'] . "\n";
        echo "Ho ten: " . $member['full_name'] . "\n";
        echo "Email: " . $member['email'] . "\n";
        echo "Trang thai: " . $member['status'] . "\n";
        echo "-------------------------\n";
    }

} catch (Throwable $e) {
    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
}
