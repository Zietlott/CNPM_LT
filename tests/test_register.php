<?php

require_once __DIR__ . '/../backend/services/AuthService.php';

// Khởi tạo AuthService
$authService = new AuthService();

// Tạo tài khoản kiểm thử
$result = $authService->register(
    'Nguyen Van Test',
    'testfitzone@example.com',
    '0987654321',
    'Test@123456'
);

// Hiển thị kết quả
print_r($result);
