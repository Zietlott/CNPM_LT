<?php

require_once __DIR__ . '/../backend/controllers/MembershipController.php';

$controller = new MembershipController();

// TEST 1: Khách chưa đăng nhập không được đăng ký gói
$_SESSION = [];

$result = $controller->registerPlan(1);

echo "TEST 1: ";
echo $result['success'] === false
    ? "PASS - Chan khach chua dang nhap\n"
    : "FAIL - Khach duoc dang ky goi\n";

// TEST 2: Admin không được đăng ký gói với quyền member
$_SESSION = [
    'member_id' => 1,
    'full_name' => 'Test Admin',
    'email' => 'admin@test.com',
    'role' => 'admin'
];

$result = $controller->registerPlan(1);

echo "TEST 2: ";
echo $result['success'] === false
    ? "PASS - Chan admin dang ky goi\n"
    : "FAIL - Admin duoc dang ky goi\n";

// TEST 3: Khách chưa đăng nhập không được xem lịch sử
$_SESSION = [];

$result = $controller->getMyHistory();

echo "TEST 3: ";
echo $result['success'] === false
    ? "PASS - Chan xem lich su\n"
    : "FAIL - Cho xem lich su\n";

// TEST 4: Admin không được xem gói hiện tại bằng quyền member
$_SESSION = [
    'member_id' => 1,
    'full_name' => 'Test Admin',
    'email' => 'admin@test.com',
    'role' => 'admin'
];

$result = $controller->getMyCurrentMembership();

echo "TEST 4: ";
echo $result['success'] === false
    ? "PASS - Chan admin xem goi member\n"
    : "FAIL - Admin xem duoc goi member\n";

// TEST 5: Danh sách gói tập được truy cập công khai
$_SESSION = [];

$result = $controller->getPlans();

echo "TEST 5: ";
echo $result['success'] === true && is_array($result['data'])
    ? "PASS - Lay danh sach goi cong khai\n"
    : "FAIL - Khong lay duoc danh sach goi\n";

$_SESSION = [];
