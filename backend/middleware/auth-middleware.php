<?php
/**
 * FitZone Authentication, Authorization & Error Handling Middleware
 * Path: backend/middleware/auth-middleware.php
 *
 * Cung cấp cơ chế xác thực, phân quyền (RBAC), kiểm tra CSRF,
 * phân tích request và chuẩn hoá xử lý lỗi cho các API endpoint và controller.
 */

require_once __DIR__ . '/../helpers/auth.php';

class AuthMiddleware
{
    /**
     * Chuẩn hoá gửi phản hồi JSON
     */
    public static function json(
        bool $success,
        string $message,
        mixed $data = null,
        int $httpStatus = 200,
        ?string $errorCode = null
    ): void {
        if (!headers_sent()) {
            http_response_code($httpStatus);
            header('Content-Type: application/json; charset=utf-8');
        }

        $payload = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($errorCode !== null) {
            $payload['errorCode'] = $errorCode;
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Bắt buộc phương thức HTTP (GET, POST, PUT, DELETE,...)
     */
    public static function requireMethod(string|array $allowedMethods): void
    {
        $methods = (array) $allowedMethods;
        $current = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');

        if (!in_array($current, array_map('strtoupper', $methods), true)) {
            if (!headers_sent()) {
                header('Allow: ' . implode(', ', $methods));
            }
            self::json(false, 'Phương thức yêu cầu không hợp lệ.', null, 405, 'METHOD_NOT_ALLOWED');
        }
    }

    /**
     * Bắt buộc đăng nhập
     */
    public static function requireLogin(bool $json = true): array
    {
        if (!isLoggedIn()) {
            if ($json) {
                self::json(false, 'Bạn cần đăng nhập để tiếp tục.', null, 401, 'UNAUTHORIZED');
            } else {
                header('Location: ../auth/login.php');
                exit;
            }
        }

        return currentUser() ?? [];
    }

    /**
     * Bắt buộc quyền hạn cụ thể (hỗ trợ 1 role hoặc danh sách role)
     */
    public static function requireRole(string|array $roles, bool $json = true): array
    {
        $user = self::requireLogin($json);
        $roleList = (array) $roles;
        $currentRole = $_SESSION['role'] ?? '';

        if (!in_array($currentRole, $roleList, true)) {
            if ($json) {
                self::json(
                    false,
                    'Bạn không có quyền thực hiện thao tác này.',
                    null,
                    403,
                    'FORBIDDEN'
                );
            } else {
                header('Location: ../auth/login.php');
                exit;
            }
        }

        return $user;
    }

    /**
     * Bắt buộc kiểm tra CSRF token cho các thao tác POST/PUT/DELETE
     */
    public static function requireCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? $_POST['csrf_token']
            ?? null;

        if (!$token || !verifyCsrfToken($token)) {
            self::json(
                false,
                'Phiên làm việc hoặc CSRF token không hợp lệ. Vui lòng tải lại trang.',
                null,
                403,
                'CSRF_TOKEN_INVALID'
            );
        }
    }

    /**
     * Đọc và parse dữ liệu JSON từ request body
     */
    public static function readJson(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (!preg_match('/^application\/json(?:\s*;|$)/i', $contentType)) {
            self::json(
                false,
                'Định dạng yêu cầu phải là application/json.',
                null,
                415,
                'UNSUPPORTED_MEDIA_TYPE'
            );
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        if (!is_array($input)) {
            self::json(
                false,
                'Dữ liệu JSON gửi lên không hợp lệ.',
                null,
                400,
                'INVALID_JSON'
            );
        }

        return $input;
    }

    /**
     * Chuẩn hoá bắt lỗi và trả về phản hồi an toàn
     */
    public static function handleError(
        Throwable $error,
        string $defaultMessage = 'Không thể hoàn tất thao tác lúc này.'
    ): void {
        error_log(sprintf(
            '[FitZone Error] %s in %s:%d',
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        ));

        // Nếu exception có chứa mã HTTP (ví dụ BookingException)
        $status = property_exists($error, 'httpStatus') && is_int($error->httpStatus)
            ? $error->httpStatus
            : 500;

        $errorCode = property_exists($error, 'errorCode') && is_string($error->errorCode)
            ? $error->errorCode
            : 'INTERNAL_SERVER_ERROR';

        $message = $status < 500 ? $error->getMessage() : $defaultMessage;

        self::json(false, $message, null, $status, $errorCode);
    }
}
