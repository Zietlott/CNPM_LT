<?php

require_once __DIR__ . '/../backend/services/BookingService.php';

$db = getDBConnection();
$scheduleIds = [];
$bookingIds = [];
$results = [];

$assert = static function (string $name, bool $passed) use (&$results): void {
    $results[] = [$name, $passed];
};

try {
    $memberId = (int) $db->query(
        "SELECT m.member_id
         FROM member m
         INNER JOIN membership ms ON ms.member_id = m.member_id
         WHERE m.role = 'member'
           AND m.status = 'active'
           AND ms.status = 'active'
           AND ms.start_date <= DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)
           AND ms.end_date >= DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)
         ORDER BY m.member_id
         LIMIT 1"
    )->fetchColumn();

    if ($memberId <= 0) {
        throw new RuntimeException('Không có hội viên hoạt động để kiểm thử.');
    }

    $workDate = date('Y-m-d', strtotime('+3 days'));
    $scheduleDefinitions = [
        [1, '13:00:00', '14:00:00'],
        [1, '13:30:00', '14:30:00'],
        [2, '13:00:00', '14:00:00']
    ];

    foreach ($scheduleDefinitions as [$trainerId, $startTime, $endTime]) {
        $stmt = $db->prepare(
            "INSERT INTO trainer_schedule
                (trainer_id, work_date, start_time, end_time, status)
             VALUES
                (:trainer_id, :work_date, :start_time, :end_time, 'available')"
        );
        $stmt->execute([
            'trainer_id' => $trainerId,
            'work_date' => $workDate,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
        $scheduleIds[] = (int) $db->lastInsertId();
    }

    $service = new BookingService($db);
    $availableSlots = $service->getAvailableSlots(1, $workDate);
    $assert(
        'available schedule is listed',
        in_array(
            $scheduleIds[0],
            array_map(
                static fn(array $slot): int => (int) $slot['schedule_id'],
                $availableSlots
            ),
            true
        )
    );

    $inactiveMemberId = (int) $db->query(
        "SELECT m.member_id
         FROM member m
         WHERE m.role = 'member'
           AND m.status = 'active'
           AND NOT EXISTS (
               SELECT 1
               FROM membership ms
               WHERE ms.member_id = m.member_id
                 AND ms.status = 'active'
                 AND ms.start_date <= DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)
                 AND ms.end_date >= DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)
           )
         ORDER BY m.member_id
         LIMIT 1"
    )->fetchColumn();

    if ($inactiveMemberId > 0) {
        try {
            $service->createBooking($inactiveMemberId, $scheduleIds[0], 'Membership test');
            $assert('inactive membership cannot create booking', false);
        } catch (BookingException $error) {
            $assert(
                'inactive membership cannot create booking',
                $error->errorCode === 'MEMBERSHIP_NOT_ACTIVE'
            );
        }
    }

    $bookingIds[] = $service->createBooking(
        $memberId,
        $scheduleIds[0],
        'Booking service test'
    );
    $stmt = $db->prepare(
        'SELECT status, membership_id FROM booking WHERE booking_id = :booking_id'
    );
    $stmt->execute(['booking_id' => $bookingIds[0]]);
    $createdBooking = $stmt->fetch();
    $assert(
        'booking is created as pending with active membership',
        $createdBooking !== false
            && $createdBooking['status'] === 'pending'
            && (int) $createdBooking['membership_id'] > 0
    );

    $memberBookings = $service->getMemberBookings($memberId);
    $otherMemberBookings = $service->getMemberBookings($inactiveMemberId);
    $assert(
        'members only receive their own bookings',
        in_array(
            $bookingIds[0],
            array_map(
                static fn(array $booking): int => (int) $booking['booking_id'],
                $memberBookings
            ),
            true
        )
        && !in_array(
            $bookingIds[0],
            array_map(
                static fn(array $booking): int => (int) $booking['booking_id'],
                $otherMemberBookings
            ),
            true
        )
    );

    foreach ([$scheduleIds[0], $scheduleIds[1], $scheduleIds[2]] as $index => $scheduleId) {
        try {
            $service->createBooking($memberId, $scheduleId, 'Conflict test');
            $assert(
                ['exact slot', 'overlapping trainer slot', 'overlapping member slot'][$index]
                    . ' is rejected',
                false
            );
        } catch (BookingException $error) {
            $assert(
                ['exact slot', 'overlapping trainer slot', 'overlapping member slot'][$index]
                    . ' is rejected',
                $error->errorCode === 'BOOKING_SLOT_UNAVAILABLE'
            );
        }
    }

    $service->cancelBooking($memberId, $bookingIds[0], 'Booking test cleanup');
    $stmt = $db->prepare(
        'SELECT status, cancel_reason FROM booking WHERE booking_id = :booking_id'
    );
    $stmt->execute(['booking_id' => $bookingIds[0]]);
    $cancelledBooking = $stmt->fetch();
    $assert(
        'booking can be cancelled more than 24 hours before start',
        $cancelledBooking !== false
            && $cancelledBooking['status'] === 'cancelled'
            && $cancelledBooking['cancel_reason'] === 'Booking test cleanup'
    );

    $availableSlots = $service->getAvailableSlots(1, $workDate);
    $assert(
        'cancelled booking releases its slot',
        in_array(
            $scheduleIds[0],
            array_map(
                static fn(array $slot): int => (int) $slot['schedule_id'],
                $availableSlots
            ),
            true
        )
    );

    $cutoffSlot = (new DateTimeImmutable('now'))->modify('+12 hours');
    $cutoffHour = (int) $cutoffSlot->format('H');

    if ($cutoffHour === 23) {
        $cutoffHour = 22;
    }

    $cutoffWorkDate = $cutoffSlot->format('Y-m-d');
    $cutoffStartTime = sprintf('%02d:00:00', $cutoffHour);
    $cutoffEndTime = sprintf('%02d:00:00', $cutoffHour + 1);
    $stmt = $db->prepare(
        "INSERT INTO trainer_schedule
            (trainer_id, work_date, start_time, end_time, status)
         VALUES
            (3, :work_date, :start_time, :end_time, 'available')"
    );
    $stmt->execute([
        'work_date' => $cutoffWorkDate,
        'start_time' => $cutoffStartTime,
        'end_time' => $cutoffEndTime
    ]);
    $scheduleIds[] = (int) $db->lastInsertId();
    $bookingIds[] = $service->createBooking(
        $memberId,
        $scheduleIds[3],
        'Cancellation cutoff test'
    );

    try {
        $service->cancelBooking($memberId, $bookingIds[1], 'Too late');
        $assert('cancellation inside 24 hours is rejected', false);
    } catch (BookingException $error) {
        $assert(
            'cancellation inside 24 hours is rejected',
            $error->errorCode === 'BOOKING_CANCELLATION_DEADLINE_PASSED'
        );
    }

    $stmt = $db->prepare(
        'SELECT status FROM booking WHERE booking_id = :booking_id'
    );
    $stmt->execute(['booking_id' => $bookingIds[1]]);
    $assert(
        'rejected cancellation leaves booking unchanged',
        $stmt->fetchColumn() === 'pending'
    );

    foreach ($results as [$name, $passed]) {
        echo ($passed ? 'PASS' : 'FAIL') . ': ' . $name . PHP_EOL;
    }

    if (in_array(false, array_column($results, 1), true)) {
        exit(1);
    }
} finally {
    foreach ($bookingIds as $bookingId) {
        $stmt = $db->prepare('DELETE FROM notification WHERE booking_id = :booking_id');
        $stmt->execute(['booking_id' => $bookingId]);
        $stmt = $db->prepare('DELETE FROM booking WHERE booking_id = :booking_id');
        $stmt->execute(['booking_id' => $bookingId]);
    }

    foreach ($scheduleIds as $scheduleId) {
        $stmt = $db->prepare(
            'DELETE FROM trainer_schedule WHERE schedule_id = :schedule_id'
        );
        $stmt->execute(['schedule_id' => $scheduleId]);
    }
}
