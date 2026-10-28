<?php

require_once __DIR__ . '/common.php';

bookingRequireMethod('GET');
bookingRequireMember();

try {
    $controller = new BookingController();
    bookingRespond([
        'success' => true,
        'data' => $controller->getMyBookings()
    ]);
} catch (Throwable $error) {
    bookingHandleError($error);
}
