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
$startAt = $input['startAt'] ?? null;
$endAt = $input['endAt'] ?? null;

if ($scheduleId === false || !is_string($note) || !is_string($startAt) || !is_string($endAt)) {
    bookingRespond([
        'success' => false,
        'message' => 'Thông tin đặt lịch không hợp lệ.',
        'errorCode' => 'INVALID_BOOKING_DATA'
    ], 422);
}

try {
    $controller = new BookingController();
    $bookingId = $controller->createMyBooking($scheduleId, $note, $startAt, $endAt);

    bookingRespond([
        'success' => true,
        'message' => 'Đã giữ chỗ trong 15 phút. Vui lòng thanh toán demo.',
        'data' => [
            'booking_id' => $bookingId,
            'status' => 'awaitingpayment'
        ]
    ], 201);
} catch (Throwable $error) {
    bookingHandleError($error);
}
