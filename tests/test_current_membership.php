<?php

require_once __DIR__ . '/../backend/services/MembershipService.php';

$db = null;

try {
    $db = getDBConnection();
    $service = new MembershipService($db);

    // TEST 1: Gói pending không được xem là active
    $membership = $service->getCurrentMembership(5);

    echo "TEST 1: ";
    echo $membership === null
        ? "PASS - Goi pending khong duoc kich hoat\n"
        : "FAIL - Member 5 dang co goi active\n";

    // TEST 2: ID không hợp lệ
    $invalidRejected = false;

    try {
        $service->getCurrentMembership(0);
    } catch (InvalidArgumentException $e) {
        $invalidRejected = true;
    }

    echo "TEST 2: ";
    echo $invalidRejected
        ? "PASS - Tu choi ID khong hop le\n"
        : "FAIL - Chap nhan ID khong hop le\n";

    // TEST 3: Tạo gói active tạm thời để kiểm thử
    $db->beginTransaction();

    $planId = $db->query(
        "SELECT plan_id
         FROM membership_plan
         WHERE is_active = 1
         LIMIT 1"
    )->fetchColumn();

    if (!$planId) {
        throw new RuntimeException('Khong co goi tap de test');
    }

    // Dùng một hội viên chưa có gói active còn hạn
    $memberId = $db->query(
        "SELECT m.member_id
         FROM member m
         WHERE m.role = 'member'
           AND m.status = 'active'
           AND NOT EXISTS (
               SELECT 1 FROM membership ms
               WHERE ms.member_id = m.member_id
                 AND ms.status = 'active'
                 AND ms.start_date <= CURDATE()
                 AND ms.end_date >= CURDATE()
           )
         LIMIT 1"
    )->fetchColumn();

    if (!$memberId) {
        throw new RuntimeException('Khong co hoi vien phu hop de test');
    }

    $stmt = $db->prepare(
        "INSERT INTO membership
            (member_id, plan_id, start_date, end_date, status)
         VALUES
            (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'active')"
    );

    $stmt->execute([(int) $memberId, (int) $planId]);

    $newMembershipId = (int) $db->lastInsertId();

    $current = $service->getCurrentMembership((int) $memberId);

    echo "TEST 3: ";
    echo $current !== null
        && (int) $current['membership_id'] === $newMembershipId
        && $current['status'] === 'active'
        ? "PASS - Lay dung goi active\n"
        : "FAIL - Khong lay dung goi active\n";

    // Hoàn tác dữ liệu test
    $db->rollBack();

    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM membership WHERE membership_id = ?"
    );
    $stmt->execute([$newMembershipId]);

    echo "TEST 4: ";
    echo (int) $stmt->fetchColumn() === 0
        ? "PASS - ROLLBACK thanh cong\n"
        : "FAIL - Du lieu test van con\n";

} catch (Throwable $e) {
    if ($db !== null && $db->inTransaction()) {
        $db->rollBack();
    }

    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
}
