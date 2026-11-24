<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../services/BookingService.php';

$service = new BookingService();
$service->cancelInvalidFutureBookings();
$service->expireHolds();
