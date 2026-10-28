<?php

require_once __DIR__ . '/common.php';

bookingRequireMethod('POST');
bookingRequireMember();
bookingRequireCsrf();

$input = bookingReadJsonInput();
$scheduleId = filter_var(
    $input['schedule_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$note = $input['note'] ?? '';

if ($scheduleId === false || !is_string($note)) {
    bookingRespond([
        'success' => false,
        'message' => 'Thông tin đặt lịch không hợp lệ.',
        'errorCode' => 'INVALID_BOOKING_DATA'
    ], 422);
}

try {
    $controller = new BookingController();
    $bookingId = $controller->createMyBooking($scheduleId, $note);

    bookingRespond([
        'success' => true,
        'message' => 'Đã gửi yêu cầu đặt lịch, vui lòng chờ xác nhận.',
        'data' => [
            'booking_id' => $bookingId,
            'status' => 'pending'
        ]
    ], 201);
} catch (Throwable $error) {
    bookingHandleError($error);
}
