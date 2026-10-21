<?php

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../helpers/auth.php';

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    // Xử lý đăng ký tài khoản
    public function register(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success' => false,
                'message' => 'Phương thức yêu cầu không hợp lệ.'
            ];
        }

        return $this->authService->register(
            $_POST['full_name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['phone'] ?? '',
            $_POST['password'] ?? ''
        );
    }

    // Xử lý đăng nhập
    public function login(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success' => false,
                'message' => 'Phương thức yêu cầu không hợp lệ.'
            ];
        }

        $result = $this->authService->login(
            $_POST['email'] ?? '',
            $_POST['password'] ?? ''
        );

        if ($result['success']) {
            loginUser($result['user']);
        }

        return $result;
    }

    // Xử lý đăng xuất
    public function logout(): array
    {
        if (!isLoggedIn()) {
            return [
                'success' => false,
                'message' => 'Bạn chưa đăng nhập.'
            ];
        }

        logoutUser();

        return [
            'success' => true,
            'message' => 'Đăng xuất thành công!'
        ];
    }
}
