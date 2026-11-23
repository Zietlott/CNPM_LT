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
        $sql = "SELECT id FROM users
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
            $this->db->beginTransaction();
            $sql = "INSERT INTO users
                    (full_name, email, phone, password_hash, role)
                    VALUES
                    (:full_name, :email, :phone, :password_hash, 'Member')";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => $passwordHash
            ]);

            $memberId = (int) $this->db->lastInsertId();
            $profile = $this->db->prepare('INSERT INTO member_profiles (user_id) VALUES (?)');
            $profile->execute([$memberId]);
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Đăng ký thành công!',
                'member_id' => $memberId
            ];

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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
        $sql = "SELECT id, full_name, email, password_hash, role, status
                FROM users
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
        if ($user['status'] !== 'Active') {
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
                'member_id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'] === 'PT' ? 'trainer' : strtolower($user['role'])
            ]
        ];
    }

    /**
     * AUTH-05: Đổi mật khẩu cho người dùng đã đăng nhập.
     */
    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword,
        string $confirmPassword
    ): array {

        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'Người dùng không hợp lệ.'
            ];
        }

        if (
            $currentPassword === '' ||
            $newPassword === '' ||
            $confirmPassword === ''
        ) {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập đầy đủ thông tin.'
            ];
        }

        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự.'
            ];
        }

        if ($newPassword !== $confirmPassword) {
            return [
                'success' => false,
                'message' => 'Xác nhận mật khẩu mới không khớp.'
            ];
        }

        if ($currentPassword === $newPassword) {
            return [
                'success' => false,
                'message' => 'Mật khẩu mới phải khác mật khẩu hiện tại.'
            ];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT password_hash
                 FROM users
                 WHERE id = ? AND status = 'Active'
                 LIMIT 1"
            );

            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Tài khoản không tồn tại hoặc không hoạt động.'
                ];
            }

            if (!password_verify(
                $currentPassword,
                $user['password_hash']
            )) {
                return [
                    'success' => false,
                    'message' => 'Mật khẩu hiện tại không chính xác.'
                ];
            }

            $newHash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            // Chỉ cập nhật nếu mật khẩu trong DB chưa bị thay đổi.
            $update = $this->db->prepare(
                "UPDATE users
                 SET password_hash = ?
                 WHERE id = ?
                   AND status = 'Active'
                   AND password_hash = ?"
            );

            $update->execute([
                $newHash,
                $userId,
                $user['password_hash']
            ]);

            if ($update->rowCount() !== 1) {
                return [
                    'success' => false,
                    'message' => 'Mật khẩu đã thay đổi trong phiên khác. Vui lòng thử lại.'
                ];
            }

            return [
                'success' => true,
                'message' => 'Đổi mật khẩu thành công!'
            ];

        } catch (PDOException $e) {
            error_log('Change password failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Có lỗi hệ thống, vui lòng thử lại.'
            ];
        }
    }

}
