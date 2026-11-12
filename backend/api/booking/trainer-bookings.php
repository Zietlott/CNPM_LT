<?php

require_once __DIR__ . '/common.php';

bookingRequireMethod('GET');
bookingRequireRole('trainer');

try {
    $controller = new BookingController();
    bookingRespond([
        'success' => true,
        'data' => $controller->getMyTrainerBookings()
    ]);
} catch (Throwable $error) {
    bookingHandleError($error);
}
