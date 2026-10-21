<?php

require_once __DIR__ . '/../backend/controllers/AuthController.php';

// Giả lập một form đăng nhập gửi dữ liệu bằng POST
$_SERVER['REQUEST_METHOD'] = 'POST';

$_POST = [
    'email' => 'testfitzone@example.com',
    'password' => 'Test@123456'
];

// Gọi Controller xử lý đăng nhập
$controller = new AuthController();
$result = $controller->login();

// Lưu trạng thái trước khi đăng xuất
$sessionSaved = isLoggedIn();
$user = currentUser();

// Dọn dẹp Session trước khi in kết quả
if ($sessionSaved) {
    logoutUser();
}
echo "=== KIEM TRA AUTH CONTROLLER ===" . PHP_EOL;
echo "Dang nhap: " . ($result['success'] ? 'Thanh cong' : 'That bai') . PHP_EOL;
echo "Thong bao: " . $result['message'] . PHP_EOL;
echo "Session: " . ($sessionSaved ? 'Da luu' : 'Chua luu') . PHP_EOL;

if ($result['success']) {
    print_r($user);
}

// Dọn dẹp Session sau kiểm thử
if (isLoggedIn()) {
    logoutUser();
}
