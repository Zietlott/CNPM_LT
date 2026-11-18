<?php

require_once __DIR__ . '/../../../backend/helpers/auth.php';

// Chỉ cho phép đăng xuất bằng POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method Not Allowed');
}

// Kiểm tra CSRF token
$csrfToken = $_POST['csrf_token'] ?? null;

if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    exit('CSRF token không hợp lệ.');
}

// Xóa session đăng nhập
if (isLoggedIn()) {
    logoutUser();
}

// Chuyển về trang đăng nhập
header('Location: login.php', true, 302);
exit;
