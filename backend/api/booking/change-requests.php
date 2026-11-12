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
$scheduleId = filter_var(
    $input['schedule_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$reason = $input['reason'] ?? '';

if ($bookingId === false || $scheduleId === false || !is_string($reason)) {
    bookingRespond([
        'success' => false,
        'message' => 'Thông tin yêu cầu đổi lịch không hợp lệ.',
        'errorCode' => 'INVALID_BOOKING_DATA'
    ], 422);
}

try {
    $controller = new BookingController();
    $requestId = $controller->createMyChangeRequest(
        $bookingId,
        $scheduleId,
        $reason
    );
    bookingRespond([
        'success' => true,
        'message' => 'Đã gửi yêu cầu đổi lịch, chờ PT duyệt.',
        'data' => ['request_id' => $requestId]
    ], 201);
} catch (Throwable $error) {
    bookingHandleError($error);
}
