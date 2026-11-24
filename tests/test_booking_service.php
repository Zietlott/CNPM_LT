<?php

require_once __DIR__ . '/../backend/services/BookingService.php';

$db = getDBConnection();
$scheduleIds = [];
$bookingIds = [];
$results = [];
$hasFailures = false;

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
        [4, '13:00:00', '14:00:00'],
        [4, '13:30:00', '14:30:00'],
        [5, '13:00:00', '14:00:00']
    ];

    foreach ($scheduleDefinitions as [$trainerId, $startTime, $endTime]) {
        $stmt = $db->prepare(
            "INSERT INTO schedules (pt_id, start_at, end_at)
             SELECT :trainer_id, TIMESTAMP(d.work_date, :start_time) - INTERVAL 7 HOUR,
                    TIMESTAMP(d.work_date, :end_time) - INTERVAL 7 HOUR
             FROM (SELECT :work_date AS work_date) d"
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
    $availableSlots = $service->getAvailableSlots(4, $workDate);
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
    $heldBooking = $stmt->fetch();
    $assert('booking initially holds a slot', $heldBooking['status'] === 'awaitingpayment');
    $assert('demo payment can fail', !$service->settleDemoPayment($memberId, $bookingIds[0], false));
    $assert('demo payment can retry', $service->settleDemoPayment($memberId, $bookingIds[0], true));
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

    $availableSlots = $service->getAvailableSlots(4, $workDate);
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

    $expiredBooking = $service->createBooking($memberId, $scheduleIds[0], 'Expiry test');
    $bookingIds[] = $expiredBooking;
    $db->prepare('UPDATE bookings SET hold_expires_at = UTC_TIMESTAMP() - INTERVAL 1 SECOND WHERE id = ?')
        ->execute([$expiredBooking]);
    $service->expireHolds();
    $expired = $db->prepare(
        'SELECT b.status AS booking_status, p.status AS payment_status
         FROM bookings b JOIN payments p ON p.booking_id = b.id WHERE b.id = ?'
    );
    $expired->execute([$expiredBooking]);
    $assert('expired hold releases booking and closes payment', $expired->fetch() === [
        'booking_status' => 'Expired', 'payment_status' => 'Failed'
    ]);
    $assert(
        'expired slot is available again',
        in_array($scheduleIds[0], array_map('intval', array_column($service->getAvailableSlots(4, $workDate), 'schedule_id')), true)
    );

    $firstHalf = $service->createBooking($memberId, $scheduleIds[0], '', $workDate . 'T13:00', $workDate . 'T13:30');
    $bookingIds[] = $firstHalf;
    $secondHalf = $service->createBooking($memberId, $scheduleIds[0], '', $workDate . 'T13:30', $workDate . 'T14:00');
    $bookingIds[] = $secondHalf;
    $paid = $service->settleDemoPayment($memberId, $secondHalf, true);
    $assert('two non-overlapping bookings fit into one work window', $firstHalf !== $secondHalf && $paid);
    $db->prepare("UPDATE users SET status = 'Locked' WHERE id = ?")->execute([$memberId]);
    try {
        $assert('temporary lock keeps existing bookings', $service->cancelInvalidFutureBookings() === 0);
        $db->prepare("UPDATE users SET status = 'Disabled' WHERE id = ?")->execute([$memberId]);
        $assert('disabled account cancels both future bookings', $service->cancelInvalidFutureBookings() === 2);
        $check = $db->prepare("SELECT COUNT(*) FROM refunds WHERE booking_id = ? AND status = 'Pending'");
        $check->execute([$secondHalf]);
        $assert('system cancellation creates a refund for successful payment', (int) $check->fetchColumn() === 1);
    } finally {
        $db->prepare("UPDATE users SET status = 'Active' WHERE id = ?")->execute([$memberId]);
    }

    $membershipEnd = $db->prepare("SELECT end_date FROM memberships WHERE member_id = ? AND status = 'Active'");
    $membershipEnd->execute([$memberId]);
    $endDay = $membershipEnd->fetchColumn();
    $endDaySlot = $db->prepare('INSERT INTO schedules (pt_id, start_at, end_at) VALUES (6, ?, ?)');
    $endDaySlot->execute([$endDay . ' 12:00:00', $endDay . ' 13:00:00']);
    $endDaySlotId = (int) $db->lastInsertId();
    $scheduleIds[] = $endDaySlotId;
    try {
        $bookingIds[] = $service->createBooking($memberId, $endDaySlotId);
        $assert('membership end date is exclusive', false);
    } catch (BookingException $error) {
        $assert('membership end date is exclusive', $error->errorCode === 'MEMBERSHIP_NOT_ACTIVE');
    }

    $databaseNow = new DateTimeImmutable(
        $db->query('SELECT UTC_TIMESTAMP() + INTERVAL 7 HOUR')->fetchColumn()
    );
    $cutoffSlot = $databaseNow->setTime(
        (int) $databaseNow->format('H'),
        0
    )->modify('+1 hour');
    $cutoffSchedule = null;
    for ($hourOffset = 0; $hourOffset < 24; $hourOffset++) {
        $candidate = $cutoffSlot->modify('+' . $hourOffset . ' hours');
        $candidateDate = $candidate->format('Y-m-d');
        $candidateStart = $candidate->format('H:i:s');
        $candidateEnd = $candidate->modify('+1 hour')->format('H:i:s');
        if ($candidateEnd === '00:00:00') {
            continue;
        }

        $stmt = $db->prepare(
            'SELECT COUNT(*)
             FROM trainer_schedule
             WHERE trainer_id = 6
               AND work_date = :work_date
               AND start_time < :end_time
               AND end_time > :start_time'
        );
        $stmt->execute([
            'work_date' => $candidateDate,
            'end_time' => $candidateEnd,
            'start_time' => $candidateStart
        ]);
        if ((int) $stmt->fetchColumn() === 0) {
            $cutoffSchedule = [
                'work_date' => $candidateDate,
                'start_time' => $candidateStart,
                'end_time' => $candidateEnd
            ];
            break;
        }
    }

    if ($cutoffSchedule === null) {
        throw new RuntimeException('Không tìm được giờ trống để test mốc hủy.');
    }

    $stmt = $db->prepare(
        "INSERT INTO schedules (pt_id, start_at, end_at)
             SELECT 6, TIMESTAMP(d.work_date, :start_time) - INTERVAL 7 HOUR,
                    TIMESTAMP(d.work_date, :end_time) - INTERVAL 7 HOUR
             FROM (SELECT :work_date AS work_date) d"
    );
    $stmt->execute([
        'work_date' => $cutoffSchedule['work_date'],
        'start_time' => $cutoffSchedule['start_time'],
        'end_time' => $cutoffSchedule['end_time']
    ]);
    $cutoffSlotId = (int) $db->lastInsertId();
    $scheduleIds[] = $cutoffSlotId;
    try {
        $bookingIds[] = $service->createBooking($memberId, $cutoffSlotId, 'Lead time test');
        $assert('booking inside 24 hours is rejected', false);
    } catch (BookingException $error) {
        $assert('booking inside 24 hours is rejected', $error->errorCode === 'BOOKING_SLOT_UNAVAILABLE');
    }

    foreach ($results as [$name, $passed]) {
        echo ($passed ? 'PASS' : 'FAIL') . ': ' . $name . PHP_EOL;
    }

    $hasFailures = in_array(false, array_column($results, 1), true);
} finally {
    foreach ($bookingIds as $bookingId) {
        $stmt = $db->prepare("DELETE FROM notifications WHERE type = 'Booking' AND reference_id = :booking_id");
        $stmt->execute(['booking_id' => $bookingId]);
        $stmt = $db->prepare("DELETE FROM audit_logs WHERE entity_type = 'Booking' AND entity_id = :booking_id");
        $stmt->execute(['booking_id' => $bookingId]);
        $stmt = $db->prepare('DELETE FROM booking_change_requests WHERE booking_id = :booking_id');
        $stmt->execute(['booking_id' => $bookingId]);
        $db->prepare('DELETE FROM refunds WHERE booking_id = :booking_id')->execute(['booking_id' => $bookingId]);
        $db->prepare('DELETE FROM payments WHERE booking_id = :booking_id')->execute(['booking_id' => $bookingId]);
        $stmt = $db->prepare('DELETE FROM bookings WHERE id = :booking_id');
        $stmt->execute(['booking_id' => $bookingId]);
    }

    foreach ($scheduleIds as $scheduleId) {
        $stmt = $db->prepare(
            'DELETE FROM schedules WHERE id = :schedule_id'
        );
        $stmt->execute(['schedule_id' => $scheduleId]);
    }
}

if ($hasFailures) {
    exit(1);
}
