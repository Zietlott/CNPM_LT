<?php

require_once __DIR__ . '/../../controllers/BookingController.php';

header('Content-Type: application/json; charset=utf-8');

function bookingRespond(array $body, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function bookingRequireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        bookingRespond([
            'success' => false,
            'message' => 'Phương thức yêu cầu không hợp lệ.'
        ], 405);
    }
}

function bookingRequireMember(): void
{
    if (!isMember()) {
        bookingRespond([
            'success' => false,
            'message' => 'Bạn cần đăng nhập bằng tài khoản hội viên.'
        ], 401);
    }
}

function bookingRequireCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (!verifyCsrfToken($token)) {
        bookingRespond([
            'success' => false,
            'message' => 'CSRF token không hợp lệ hoặc bị thiếu.'
        ], 403);
    }
}

function bookingReadJsonInput(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (!preg_match('/^application\/json(?:\s*;|$)/i', $contentType)) {
        bookingRespond([
            'success' => false,
            'message' => 'Content-Type phải là application/json.'
        ], 415);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        bookingRespond([
            'success' => false,
            'message' => 'Dữ liệu JSON không hợp lệ.'
        ], 400);
    }

    return $input;
}

function bookingHandleError(Throwable $error): void
{
    if ($error instanceof BookingException) {
        bookingRespond([
            'success' => false,
            'message' => $error->getMessage(),
            'errorCode' => $error->errorCode
        ], $error->httpStatus);
    }

    error_log('Booking API error: ' . $error->getMessage());
    bookingRespond([
        'success' => false,
        'message' => 'Không thể xử lý yêu cầu đặt lịch lúc này.'
    ], 500);
}
