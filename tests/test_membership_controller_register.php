<?php

require_once __DIR__ . '/../backend/controllers/MembershipController.php';

$db = null;

try {
    $db = getDBConnection();
    $db->beginTransaction();

    // Tìm hội viên hợp lệ chưa có gói pending/active
    $sql = "SELECT m.member_id
            FROM member m
            WHERE m.role = 'member'
              AND m.status = 'active'
              AND NOT EXISTS (
                  SELECT 1 FROM membership ms
                  WHERE ms.member_id = m.member_id
                    AND ms.status IN ('pending', 'active')
              )
            LIMIT 1";

    $memberId = $db->query($sql)->fetchColumn();

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

    // Giả lập session hội viên
    $_SESSION = [
        'member_id' => (int) $memberId,
        'full_name' => 'Test Member',
        'email' => 'member@test.com',
        'role' => 'member'
    ];

    $controller = new MembershipController();

    // Dùng Reflection để Controller và test chia sẻ PDO
    $property = new ReflectionProperty(
        MembershipController::class,
        'service'
    );
    $property->setAccessible(true);
    $property->setValue(
        $controller,
        new MembershipService($db)
    );

    // TEST 1: Hội viên đăng ký gói thành công
    $result = $controller->registerPlan((int) $planId);

    $passed = $result['success'] === true
        && isset($result['membership_id']);

    echo "TEST 1: ";
    echo $passed
        ? "PASS - Member dang ky qua Controller\n"
        : "FAIL - Dang ky khong thanh cong\n";

    if (!$passed) {
        throw new RuntimeException(
            $result['message'] ?? 'Controller tra ve loi'
        );
    }

    $membershipId = (int) $result['membership_id'];

    // TEST 2: Gói mới phải ở trạng thái pending
    $stmt = $db->prepare(
        "SELECT status, start_date, end_date
         FROM membership
         WHERE membership_id = ?"
    );
    $stmt->execute([$membershipId]);
    $membership = $stmt->fetch();

    $passed = $membership !== false
        && $membership['status'] === 'pending'
        && $membership['start_date'] === null
        && $membership['end_date'] === null;

    echo "TEST 2: ";
    echo $passed
        ? "PASS - Goi dang cho thanh toan\n"
        : "FAIL - Goi bi kich hoat sai\n";

    // TEST 3: Chặn đăng ký trùng
    $duplicate = $controller->registerPlan((int) $planId);

    echo "TEST 3: ";
    echo $duplicate['success'] === false
        ? "PASS - Chan dang ky trung\n"
        : "FAIL - Cho phep dang ky trung\n";

    // TEST 4: Lịch sử có gói vừa đăng ký
    $history = $controller->getMyHistory();

    $found = false;

    if ($history['success'] === true) {
        foreach ($history['data'] as $item) {
            if ((int) $item['membership_id'] === $membershipId) {
                $found = true;
                break;
            }
        }
    }

    echo "TEST 4: ";
    echo $found
        ? "PASS - Lich su co goi vua dang ky\n"
        : "FAIL - Lich su thieu goi\n";

    // TEST 5: Gói pending chưa được xem là gói hiện tại
    $current = $controller->getMyCurrentMembership();

    echo "TEST 5: ";
    echo $current['success'] === true
        && $current['data'] === null
        ? "PASS - Goi pending chua active\n"
        : "FAIL - Goi pending bi nhan la active\n";

    // Hoàn tác dữ liệu kiểm thử
    $db->rollBack();

    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM membership WHERE membership_id = ?"
    );
    $stmt->execute([$membershipId]);

    echo "TEST 6: ";
    echo (int) $stmt->fetchColumn() === 0
        ? "PASS - ROLLBACK thanh cong\n"
        : "FAIL - Du lieu test van con\n";

} catch (Throwable $e) {
    if ($db !== null && $db->inTransaction()) {
        $db->rollBack();
    }

    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $_SESSION = [];
}
