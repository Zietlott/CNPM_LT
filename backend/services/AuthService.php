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

    /**
     * AUTH-06: Yêu cầu đặt lại mật khẩu qua email.
     */
    public function requestPasswordReset(string $email): array
    {
        $email = strtolower(trim($email));

        $genericResponse = [
            'success' => true,
            'message' => 'Nếu email đã đăng ký và tài khoản đang hoạt động, chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu.'
        ];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Email không hợp lệ.'
            ];
        }

        try {
            $this->db->beginTransaction();

            // Khóa dòng tài khoản để tuần tự hóa các yêu cầu
            // đặt lại mật khẩu cho cùng một người dùng.
            $stmt = $this->db->prepare(
                "SELECT id
                 FROM users
                 WHERE email = ?
                   AND status = 'Active'
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->db->commit();
                return $genericResponse;
            }

            $userId = (int) $user['id'];

            // Chống gửi yêu cầu liên tục trong vòng 60 giây.
            $rateLimit = $this->db->prepare(
                "SELECT id
                 FROM password_reset_tokens
                 WHERE user_id = ?
                   AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
                 LIMIT 1"
            );

            $rateLimit->execute([$userId]);

            if ($rateLimit->fetch()) {
                $this->db->commit();
                return $genericResponse;
            }

            // Token gốc chỉ dùng để gửi qua email.
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            // Vô hiệu hóa các token cũ chưa sử dụng.
            $invalidate = $this->db->prepare(
                "UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE user_id = ?
                   AND used_at IS NULL"
            );

            $invalidate->execute([$userId]);

            // Chỉ lưu hash, token hết hạn sau 15 phút.
            $insert = $this->db->prepare(
                "INSERT INTO password_reset_tokens
                    (user_id, token_hash, expires_at)
                 VALUES
                    (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))"
            );

            $insert->execute([
                $userId,
                $tokenHash
            ]);

            $tokenId = (int) $this->db->lastInsertId();

            $this->db->commit();

            // Chỉ gửi email sau khi lưu token thành công.
            require_once __DIR__ . '/MailService.php';

            $mailService = new MailService();

            if (!$mailService->sendPasswordResetEmail($email, $token)) {
                // Token chưa gửi được thì vô hiệu hóa.
                $disable = $this->db->prepare(
                    "UPDATE password_reset_tokens
                     SET used_at = NOW()
                     WHERE id = ?
                       AND used_at IS NULL"
                );

                $disable->execute([$tokenId]);

                error_log('Password reset email could not be delivered.');
            }

            return $genericResponse;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            // Không ghi token hoặc URL khôi phục vào log.
            error_log('Password reset request failed: ' . get_class($e));

            return [
                'success' => false,
                'message' => 'Có lỗi hệ thống, vui lòng thử lại sau.'
            ];
        }
    }

    /**
     * AUTH-07: Đặt lại mật khẩu bằng token gửi qua email.
     */
    public function resetPassword(
        string $token,
        string $newPassword,
        string $confirmPassword
    ): array {
        $token = trim($token);

        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return [
                'success' => false,
                'message' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
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
                'message' => 'Xác nhận mật khẩu không khớp.'
            ];
        }

        try {
            $this->db->beginTransaction();

            // Khóa bản ghi token để ngăn sử dụng đồng thời.
            $stmt = $this->db->prepare(
                "SELECT id, user_id
                 FROM password_reset_tokens
                 WHERE token_hash = ?
                   AND used_at IS NULL
                   AND expires_at > NOW()
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->execute([hash('sha256', $token)]);
            $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resetRecord) {
                $this->db->rollBack();

                return [
                    'success' => false,
                    'message' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
                ];
            }

            $userId = (int) $resetRecord['user_id'];

            // Chỉ cho phép đặt lại mật khẩu cho tài khoản đang hoạt động.
            $userStmt = $this->db->prepare(
                "SELECT id
                 FROM users
                 WHERE id = ?
                   AND status = 'Active'
                 LIMIT 1
                 FOR UPDATE"
            );

            $userStmt->execute([$userId]);

            if (!$userStmt->fetch(PDO::FETCH_ASSOC)) {
                $this->db->rollBack();

                return [
                    'success' => false,
                    'message' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
                ];
            }

            $newHash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            // Cập nhật mật khẩu.
            $update = $this->db->prepare(
                "UPDATE users
                 SET password_hash = ?
                 WHERE id = ?
                   AND status = 'Active'"
            );

            $update->execute([$newHash, $userId]);

            // Vô hiệu hóa tất cả token đặt lại mật khẩu của tài khoản.
            $invalidate = $this->db->prepare(
                "UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE user_id = ?
                   AND used_at IS NULL"
            );

            $invalidate->execute([$userId]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập bằng mật khẩu mới.'
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log(
                'Reset password failed: ' . get_class($e)
            );

            return [
                'success' => false,
                'message' => 'Có lỗi hệ thống, vui lòng thử lại sau.'
            ];
        }
    }

}
