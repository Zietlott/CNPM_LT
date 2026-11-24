<?php

require_once __DIR__ . '/common.php';

bookingRequireMethod('POST');
bookingRequireCsrf();
$input = bookingReadJsonInput();

$bookingId = filter_var(
    $input['booking_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$requestId = filter_var(
    $input['request_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$action = $input['action'] ?? null;
$reason = $input['reason'] ?? '';

if (
    $bookingId === false
    || $requestId === false
    || !is_string($action)
    || !is_string($reason)
) {
    bookingRespond([
        'success' => false,
        'message' => 'Thông tin yêu cầu đổi lịch không hợp lệ.',
        'errorCode' => 'INVALID_BOOKING_DATA'
    ], 422);
}

try {
    $controller = new BookingController();
    if ($action === 'cancel') {
        bookingRequireRole('member');
        $controller->cancelMyChangeRequest($bookingId, $requestId);
    } elseif (in_array($action, ['approve', 'reject'], true)) {
        bookingRequireRole('trainer');
        $approved = $controller->reviewMyChangeRequest(
            $bookingId,
            $requestId,
            $action === 'approve',
            $reason
        );
    } else {
        throw new BookingException(
            'Thao tác yêu cầu đổi lịch không hợp lệ.',
            'INVALID_BOOKING_DATA',
            422
        );
    }

    bookingRespond([
        'success' => true,
        'message' => $action === 'cancel'
            ? 'Đã hủy yêu cầu đổi lịch.'
            : ($approved ? 'Đã duyệt yêu cầu đổi lịch.' : 'Yêu cầu đổi lịch đã bị từ chối.'),
        'data' => ['approved' => $approved ?? null]
    ]);
} catch (Throwable $error) {
    bookingHandleError($error);
}
