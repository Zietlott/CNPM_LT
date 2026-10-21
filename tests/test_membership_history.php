<?php

require_once __DIR__ . '/../backend/services/MembershipService.php';

try {
    $db = getDBConnection();
    $service = new MembershipService($db);

    // Chọn một hội viên đã từng đăng ký gói
    $sql = "SELECT member_id
            FROM membership
            ORDER BY membership_id DESC
            LIMIT 1";

    $memberId = $db->query($sql)->fetchColumn();

    if (!$memberId) {
        echo "SKIP - Chua co du lieu lich su dang ky\n";
        exit(0);
    }

    // TEST 1: Lấy lịch sử thành công
    $history = $service->getMembershipHistory((int) $memberId);

    echo "TEST 1: ";
    echo count($history) > 0
        ? "PASS - Lay lich su thanh cong\n"
        : "FAIL - Khong tim thay lich su\n";

    // TEST 2: Chỉ trả về dữ liệu của đúng hội viên
    $correctMember = true;

    foreach ($history as $item) {
        if ((int) $item['member_id'] !== (int) $memberId) {
            $correctMember = false;
            break;
        }
    }

    echo "TEST 2: ";
    echo $correctMember
        ? "PASS - Dung hoi vien\n"
        : "FAIL - Du lieu sai hoi vien\n";

    // TEST 3: Từ chối ID không hợp lệ
    $invalidIdRejected = false;

    try {
        $service->getMembershipHistory(0);
    } catch (InvalidArgumentException $e) {
        $invalidIdRejected = true;
    }

    echo "TEST 3: ";
    echo $invalidIdRejected
        ? "PASS - Tu choi ID khong hop le\n"
        : "FAIL - Chap nhan ID khong hop le\n";

    echo "\nMember ID: $memberId\n";
    echo "Tong so lan dang ky: " . count($history) . "\n";

    foreach ($history as $item) {
        echo "-------------------------\n";
        echo "Goi: " . $item['plan_name'] . "\n";
        echo "Trang thai: " . $item['status'] . "\n";
        echo "Ngay bat dau: " . ($item['start_date'] ?? 'NULL') . "\n";
        echo "Ngay ket thuc: " . ($item['end_date'] ?? 'NULL') . "\n";
    }

} catch (Throwable $e) {
    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
}