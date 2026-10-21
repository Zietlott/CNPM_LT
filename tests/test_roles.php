<?php
require_once __DIR__ . '/../backend/helpers/auth.php';

// Giả lập trạng thái chưa đăng nhập
$_SESSION = [];

echo "1. Chua dang nhap: ";
echo !isAdmin() && !isMember() ? "PASS\n" : "FAIL\n";

// Giả lập tài khoản Member
$_SESSION['member_id'] = 1;
$_SESSION['role'] = 'member';

echo "2. Tai khoan Member: ";
echo isMember() && !isAdmin() ? "PASS\n" : "FAIL\n";

// Giả lập tài khoản Admin
$_SESSION['member_id'] = 2;
$_SESSION['role'] = 'admin';

echo "3. Tai khoan Admin: ";
echo isAdmin() && !isMember() ? "PASS\n" : "FAIL\n";

// Dọn dữ liệu kiểm thử
$_SESSION = [];

