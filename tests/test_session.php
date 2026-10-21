<?php

require_once __DIR__ . '/../backend/services/AuthService.php';
require_once __DIR__ . '/../backend/helpers/auth.php';

$authService = new AuthService();

// Bước 1: Đăng nhập
$result = $authService->login(
    'testfitzone@example.com',
    'Test@123456'
);

if (!$result['success']) {
    echo "Dang nhap that bai: " . $result['message'] . PHP_EOL;
    exit;
}

// Bước 2: Lưu thông tin vào Session
loginUser($result['user']);

echo "=== SAU KHI DANG NHAP ===" . PHP_EOL;
echo "Da dang nhap: " . (isLoggedIn() ? 'Co' : 'Khong') . PHP_EOL;

print_r(currentUser());

// Bước 3: Đăng xuất
logoutUser();

echo PHP_EOL . "=== SAU KHI DANG XUAT ===" . PHP_EOL;
echo "Da dang nhap: " . (isLoggedIn() ? 'Co' : 'Khong') . PHP_EOL;
