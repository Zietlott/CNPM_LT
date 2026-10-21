<?php

require_once __DIR__ . '/../backend/services/MembershipService.php';

$db = null;

try {
    $db = getDBConnection();
    $service = new MembershipService($db);

    // Bắt đầu Transaction để không lưu dữ liệu test
    $db->beginTransaction();

    // Chọn hội viên đang hoạt động, chưa có gói pending/active
    $sql = "SELECT m.member_id
            FROM member m
            WHERE m.role = 'member'
              AND m.status = 'active'
              AND NOT EXISTS (
                  SELECT 1
                  FROM membership ms
                  WHERE ms.member_id = m.member_id
                    AND ms.status IN ('pending', 'active')
              )
            LIMIT 1";

    $memberId = $db->query($sql)->fetchColumn();

    // Chọn một gói đang được bán
    $planId = $db->query(
        "SELECT plan_id
         FROM membership_plan
         WHERE is_active = 1
         LIMIT 1"
    )->fetchColumn();

    if (!$memberId || !$planId) {
        throw new RuntimeException(
            'Khong co hoi vien hoac goi tap phu hop de test'
        );
    }

    // TEST 1: Đăng ký gói thành công
    $membershipId = $service->registerMembership(
        (int) $memberId,
        (int) $planId
    );

    $stmt = $db->prepare(
        "SELECT status, start_date, end_date
         FROM membership
         WHERE membership_id = ?"
    );
    $stmt->execute([$membershipId]);
    $membership = $stmt->fetch();

    echo "TEST 1: ";
    echo $membership !== false
        && $membership['status'] === 'pending'
        && $membership['start_date'] === null
        && $membership['end_date'] === null
        ? "PASS - Tao goi pending thanh cong\n"
        : "FAIL - Trang thai dang ky khong dung\n";

    // TEST 2: Không cho đăng ký trùng khi còn pending
    $duplicateRejected = false;

    try {
        $service->registerMembership(
            (int) $memberId,
            (int) $planId
        );
    } catch (InvalidArgumentException $e) {
        $duplicateRejected = true;
    }

    echo "TEST 2: ";
    echo $duplicateRejected
        ? "PASS - Chan dang ky trung\n"
        : "FAIL - Cho phep dang ky trung\n";

    // TEST 3: Từ chối gói không tồn tại
    $invalidPlanRejected = false;

    try {
        $service->registerMembership(
            (int) $memberId,
            999999
        );
    } catch (InvalidArgumentException $e) {
        $invalidPlanRejected = true;
    }

    echo "TEST 3: ";
    echo $invalidPlanRejected
        ? "PASS - Tu choi goi khong ton tai\n"
        : "FAIL - Chap nhan goi khong ton tai\n";

    // Hoàn tác toàn bộ thay đổi
    $db->rollBack();

    // TEST 4: Đăng ký test không còn trong database
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM membership
         WHERE membership_id = ?"
    );
    $stmt->execute([$membershipId]);

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
