<?php

require_once __DIR__ . '/common.php';

bookingRequireRole('trainer');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    try {
        $controller = new BookingController();
        bookingRespond([
            'success' => true,
            'data' => $controller->getMyTrainerSchedules()
        ]);
    } catch (Throwable $error) {
        bookingHandleError($error);
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, DELETE');
    bookingRespond([
        'success' => false,
        'message' => 'Phương thức yêu cầu không hợp lệ.'
    ], 405);
}

bookingRequireCsrf();
$input = bookingReadJsonInput();

try {
    $controller = new BookingController();
    if ($method === 'DELETE') {
        $scheduleId = filter_var(
            $input['schedule_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($scheduleId === false) {
            throw new BookingException(
                'Khung giờ không hợp lệ.',
                'INVALID_BOOKING_DATA',
                422
            );
        }

        $controller->deactivateMyTrainerSchedule($scheduleId);
        bookingRespond([
            'success' => true,
            'message' => 'Đã khóa khung giờ.'
        ]);
    }

    $workDate = $input['work_date'] ?? null;
    $startTime = $input['start_time'] ?? null;
    $endTime = $input['end_time'] ?? null;
    if (
        !is_string($workDate)
        || !is_string($startTime)
        || !is_string($endTime)
    ) {
        throw new BookingException(
            'Ngày và giờ làm việc không hợp lệ.',
            'INVALID_BOOKING_DATA',
            422
        );
    }

    if ($method === 'POST') {
        $scheduleId = $controller->createMyTrainerSchedule(
            $workDate,
            $startTime,
            $endTime
        );
        bookingRespond([
            'success' => true,
            'message' => 'Đã thêm khung giờ làm việc.',
            'data' => ['schedule_id' => $scheduleId]
        ], 201);
    }

    $scheduleId = filter_var(
        $input['schedule_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($scheduleId === false) {
        throw new BookingException(
            'Khung giờ không hợp lệ.',
            'INVALID_BOOKING_DATA',
            422
        );
    }

    $controller->updateMyTrainerSchedule(
        $scheduleId,
        $workDate,
        $startTime,
        $endTime
    );
    bookingRespond([
        'success' => true,
        'message' => 'Đã cập nhật khung giờ.'
    ]);
} catch (Throwable $error) {
    bookingHandleError($error);
}
