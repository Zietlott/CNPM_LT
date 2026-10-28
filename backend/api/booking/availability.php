<?php

require_once __DIR__ . '/common.php';

bookingRequireMethod('GET');
bookingRequireMember();

$trainerIdInput = $_GET['trainer_id'] ?? null;
$trainerId = $trainerIdInput === null
    ? null
    : filter_var(
        $trainerIdInput,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
$workDate = $_GET['work_date'] ?? null;

try {
    $controller = new BookingController();

    if ($trainerId === false || ($workDate !== null && !is_string($workDate))) {
        throw new BookingException(
            'Huấn luyện viên hoặc ngày tập không hợp lệ.',
            'INVALID_BOOKING_DATA',
            422
        );
    }

    if ($trainerId === null && $workDate === null) {
        bookingRespond([
            'success' => true,
            'data' => [
                'trainers' => $controller->getAvailableTrainers(),
                'slots' => []
            ]
        ]);
    }

    if ($trainerId === null || $workDate === null || $workDate === '') {
        throw new BookingException(
            'Cần chọn huấn luyện viên và ngày tập.',
            'INVALID_BOOKING_DATA',
            422
        );
    }

    bookingRespond([
        'success' => true,
        'data' => [
            'trainers' => [],
            'slots' => $controller->getAvailableSlots($trainerId, $workDate)
        ]
    ]);
} catch (Throwable $error) {
    bookingHandleError($error);
}
