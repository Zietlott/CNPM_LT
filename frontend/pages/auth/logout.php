<?php

require_once __DIR__ . '/../../../backend/helpers/auth.php';

// Xóa Session nếu người dùng đang đăng nhập
if (isLoggedIn()) {
    logoutUser();
}

// Chuyển về trang đăng nhập
header('Location: login.php');
exit;
