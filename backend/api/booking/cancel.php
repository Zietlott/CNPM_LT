<?php

require_once __DIR__ . '/common.php';

bookingRequireMethod('POST');
bookingRequireMember();
bookingRequireCsrf();

$input = bookingReadJsonInput();
$bookingId = filter_var(
    $input['booking_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$reason = $input['reason'] ?? '';

if ($bookingId === false || !is_string($reason)) {
    bookingRespond([
        'success' => false,
        'message' => 'Thông tin hủy lịch không hợp lệ.',
        'errorCode' => 'INVALID_BOOKING_DATA'
    ], 422);
}

try {
    $controller = new BookingController();
    $controller->cancelMyBooking($bookingId, $reason);

    bookingRespond([
        'success' => true,
        'message' => 'Đã hủy lịch tập thành công.'
    ]);
} catch (Throwable $error) {
    bookingHandleError($error);
}
