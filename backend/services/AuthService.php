<?php

require_once __DIR__ . '/../config/database.php';

class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    public function register(
        string $fullName,
        string $email,
        string $phone,
        string $password
    ): array {

        // Xóa khoảng trắng thừa
        $fullName = trim($fullName);
        $email = strtolower(trim($email));
        $phone = trim($phone);

        // Kiểm tra dữ liệu đầu vào
        if ($fullName === '' || $email === '' ||
            $phone === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập đầy đủ thông tin.'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Email không hợp lệ.'
            ];
        }

        if (strlen($password) < 8) {
            return [
                'success' => false,
                'message' => 'Mật khẩu phải có ít nhất 8 ký tự.'
            ];
        }

        // Kiểm tra email hoặc số điện thoại đã tồn tại
        $sql = "SELECT member_id FROM member
                WHERE email = :email OR phone = :phone
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'phone' => $phone
        ]);

        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Email hoặc số điện thoại đã được sử dụng.'
            ];
        }

        // Băm mật khẩu
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Thêm tài khoản vào database
        try {
            $sql = "INSERT INTO member
                    (full_name, email, phone, password_hash)
                    VALUES
                    (:full_name, :email, :phone, :password_hash)";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => $passwordHash
            ]);

            return [
                'success' => true,
                'message' => 'Đăng ký thành công!',
                'member_id' => (int) $this->db->lastInsertId()
            ];

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return [
                    'success' => false,
                    'message' => 'Email hoặc số điện thoại đã được sử dụng.'
                ];
            }

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Có lỗi hệ thống, vui lòng thử lại.'
            ];
        }
    }

    public function login(
        string $email,
        string $password
    ): array {

        $email = strtolower(trim($email));

        // Kiểm tra dữ liệu đầu vào
        if ($email === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập email và mật khẩu.'
            ];
        }

        // Tìm tài khoản theo email
        $sql = "SELECT member_id, full_name, email,
                    password_hash, role, status
                FROM member
                WHERE email = :email
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'email' => $email
        ]);

        $user = $stmt->fetch();

        // Kiểm tra tài khoản và mật khẩu
        if (!$user || !password_verify(
            $password,
            $user['password_hash']
        )) {
            return [
                'success' => false,
                'message' => 'Email hoặc mật khẩu không chính xác.'
            ];
        }

        // Kiểm tra tài khoản bị khóa
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Tài khoản đã bị khóa.'
            ];
        }

        // Đăng nhập thành công
        return [
            'success' => true,
            'message' => 'Đăng nhập thành công!',
            'user' => [
                'member_id' => (int) $user['member_id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }
}
