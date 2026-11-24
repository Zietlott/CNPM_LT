<?php

require_once __DIR__ . '/common.php';

bookingRequireMethod('POST');
bookingRequireMember();
bookingRequireCsrf();

$input = bookingReadJsonInput();
$bookingId = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$succeed = $input['succeed'] ?? true;

try {
    if ($bookingId === false || !is_bool($succeed)) {
        throw new BookingException('Thông tin thanh toán không hợp lệ.', 'INVALID_BOOKING_DATA', 422);
    }
    $paid = (new BookingService())->settleDemoPayment((int) $_SESSION['member_id'], $bookingId, $succeed);
    bookingRespond([
        'success' => true,
        'message' => $paid ? 'Đã thanh toán demo. Lịch đang chờ PT xác nhận.' : 'Thanh toán demo thất bại. Bạn có thể thử lại.',
        'data' => ['paid' => $paid]
    ]);
} catch (Throwable $error) {
    bookingHandleError($error);
}
