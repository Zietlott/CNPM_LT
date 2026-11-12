<?php

require_once __DIR__ . '/common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    bookingRespond([
        'success' => false,
        'message' => 'Phương thức yêu cầu không hợp lệ.'
    ], 405);
}

if ($method === 'GET') {
    if (!isAdmin() && !isStaff()) {
        bookingRequireRole('admin');
    }
    $status = $_GET['status'] ?? null;
    if ($status !== null && !is_string($status)) {
        bookingRespond([
            'success' => false,
            'message' => 'Trạng thái booking không hợp lệ.'
        ], 422);
    }

    try {
        $controller = new BookingController();
        bookingRespond([
            'success' => true,
            'data' => isAdmin()
                ? $controller->getAdminBookings($status)
                : $controller->getMyStaffBookings()
        ]);
    } catch (Throwable $error) {
        bookingHandleError($error);
    }
}

if (!isAdmin() && !isStaff()) {
    bookingRequireRole('admin');
}
bookingRequireCsrf();
$input = bookingReadJsonInput();
$bookingId = filter_var(
    $input['booking_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$action = $input['action'] ?? null;
$reason = $input['reason'] ?? '';

if ($bookingId === false || !is_string($action) || !is_string($reason)) {
    bookingRespond([
        'success' => false,
        'message' => 'Thông tin xử lý booking không hợp lệ.',
        'errorCode' => 'INVALID_BOOKING_DATA'
    ], 422);
}

try {
    $controller = new BookingController();
    if (isAdmin()) {
        $controller->handleAdminBooking($bookingId, $action, $reason);
    } else {
        $controller->handleStaffBooking($bookingId, $action, $reason);
    }
    bookingRespond([
        'success' => true,
        'message' => 'Đã cập nhật trạng thái booking.'
    ]);
} catch (Throwable $error) {
    bookingHandleError($error);
}
