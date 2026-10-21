<?php

require_once __DIR__ . '/../backend/services/AuthService.php';

// Khởi tạo AuthService
$authService = new AuthService();

// Kiểm thử đăng nhập
$result = $authService->login(
    'testfitzone@example.com',
    'Test@123456'
);

// Hiển thị kết quả
print_r($result);
