<?php

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lưu thông tin người dùng sau khi đăng nhập
function loginUser(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['member_id'] = $user['member_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
}

// Kiểm tra người dùng đã đăng nhập chưa
function isLoggedIn(): bool
{
    if (!isset($_SESSION['member_id'])) {
        return false;
    }

    $memberId = filter_var(
        $_SESSION['member_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($memberId === false) {
        $_SESSION = [];
        return false;
    }

    try {
        require_once __DIR__ . '/../config/database.php';

        $pdo = getDBConnection();

        $stmt = $pdo->prepare(
            "SELECT role, status FROM member WHERE member_id = ? LIMIT 1"
        );

        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member || $member['status'] !== 'active') {
            $_SESSION = [];
            return false;
        }

        // Đồng bộ quyền mới nhất từ database
        $_SESSION['role'] = $member['role'];

        return true;

    } catch (Throwable $e) {
        error_log('Auth session validation error: ' . $e->getMessage());

        // Không cấp quyền khi không xác minh được tài khoản
        return false;
    }
}


// Lấy thông tin người dùng hiện tại
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'member_id' => $_SESSION['member_id'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}

// Đăng xuất
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}


// Kiểm tra người dùng có quyền được yêu cầu không
function hasRole(string $role): bool
{
    return isLoggedIn()
        && isset($_SESSION['role'])
        && $_SESSION['role'] === $role;
}

// Kiểm tra người dùng có phải Admin không
function isAdmin(): bool
{
    return hasRole('admin');
}

// Kiểm tra người dùng có phải Member không
function isMember(): bool
{
    return hasRole('member');
}


/**
 * Tạo hoặc lấy CSRF token của session hiện tại.
 */
function getCsrfToken(): string
{
    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Kiểm tra CSRF token do client gửi lên.
 */
function verifyCsrfToken(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && hash_equals(getCsrfToken(), $token);
}


