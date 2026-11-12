<?php

require_once __DIR__ . '/../backend/services/BookingService.php';

$db = getDBConnection();
$service = new BookingService($db);
$scheduleIds = [];
$bookingIds = [];
$accountIds = [];
$trainerId = null;
$results = [];
$hasFailures = false;

$assert = static function (string $name, bool $passed) use (&$results): void {
    $results[] = [$name, $passed];
};

try {
    $workDate = (new DateTimeImmutable(
        $db->query('SELECT CURRENT_DATE')->fetchColumn()
    ))->modify('+3 days')->format('Y-m-d');
    $memberId = (int) $db->query(
        "SELECT m.member_id
         FROM member m
         INNER JOIN membership ms ON ms.member_id = m.member_id
         WHERE m.role = 'member'
           AND m.status = 'active'
           AND ms.status = 'active'
           AND ms.start_date <= " . $db->quote($workDate) . "
           AND ms.end_date >= " . $db->quote($workDate) . "
         ORDER BY m.member_id
         LIMIT 1"
    )->fetchColumn();
    $trainerId = (int) $db->query(
        "SELECT trainer_id
         FROM personal_trainer
         WHERE is_active = 1 AND member_id IS NULL
         ORDER BY trainer_id
         LIMIT 1"
    )->fetchColumn();
    $adminId = (int) $db->query(
        "SELECT member_id FROM member WHERE role = 'admin' AND status = 'active'
         ORDER BY member_id LIMIT 1"
    )->fetchColumn();

    if ($memberId <= 0 || $trainerId <= 0 || $adminId <= 0) {
        throw new RuntimeException(
            'Workflow tests require an active member, an unlinked active PT, and an admin.'
        );
    }

    $nonce = bin2hex(random_bytes(5));
    $phone = '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    $trainerAccountId = $service->createTrainerAccount(
        $trainerId,
        'workflow-pt-' . $nonce . '@example.test',
        $phone,
        'WorkflowPT!123'
    );
    $accountIds[] = $trainerAccountId;
    $staffPhone = '8' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    $staffId = $service->createStaffAccount(
        'Booking Workflow Staff',
        'workflow-staff-' . $nonce . '@example.test',
        $staffPhone,
        'WorkflowStaff!123',
        false,
        false
    );
    $accountIds[] = $staffId;

    $startCandidates = [];
    for ($hour = 6; $hour <= 22; $hour++) {
        $startCandidates[] = sprintf('%02d:00:00', $hour);
    }
    foreach ($startCandidates as $startTime) {
        try {
            $scheduleIds[] = $service->createTrainerSchedule(
                $trainerAccountId,
                $workDate,
                $startTime,
                date('H:i:s', strtotime($startTime . ' +30 minutes'))
            );
        } catch (BookingException $error) {
            if ($error->errorCode !== 'TRAINER_SCHEDULE_CONFLICT') {
                throw $error;
            }
        }
        if (count($scheduleIds) === 3) {
            break;
        }
    }

    if (count($scheduleIds) !== 3) {
        throw new RuntimeException('Không tạo được đủ khung giờ để kiểm thử workflow.');
    }

    $trainerSchedules = array_map(
        static fn(array $schedule): int => (int) $schedule['schedule_id'],
        $service->getTrainerSchedules($trainerAccountId)
    );
    $assert(
        'PT schedule is linked to its account',
        count(array_intersect($scheduleIds, $trainerSchedules)) === 3
    );

    $bookingIds[] = $service->createBooking(
        $memberId,
        $scheduleIds[0],
        'Workflow confirmation test'
    );
    $bookingId = $bookingIds[0];
    $service->updateTrainerBooking($trainerAccountId, $bookingId, 'confirm');
    $stmt = $db->prepare('SELECT status FROM booking WHERE booking_id = :booking_id');
    $stmt->execute(['booking_id' => $bookingId]);
    $assert(
        'PT can confirm its own member booking',
        $stmt->fetchColumn() === 'confirmed'
    );

    $changeRequestId = $service->createChangeRequest(
        $memberId,
        $bookingId,
        $scheduleIds[1],
        'Change to another available time'
    );
    $stmt = $db->prepare(
        'SELECT status, schedule_id FROM booking WHERE booking_id = :booking_id'
    );
    $stmt->execute(['booking_id' => $bookingId]);
    $unchangedBooking = $stmt->fetch();
    $assert(
        'change request leaves the original booking intact until PT review',
        $unchangedBooking['status'] === 'confirmed'
            && (int) $unchangedBooking['schedule_id'] === $scheduleIds[0]
    );

    $service->reviewChangeRequest(
        $trainerAccountId,
        $bookingId,
        $changeRequestId,
        true
    );
    $stmt = $db->prepare(
        'SELECT b.status, b.schedule_id, cr.status AS request_status
         FROM booking b
         INNER JOIN booking_change_request cr ON cr.booking_id = b.booking_id
         WHERE b.booking_id = :booking_id AND cr.request_id = :request_id'
    );
    $stmt->execute([
        'booking_id' => $bookingId,
        'request_id' => $changeRequestId
    ]);
    $approvedChange = $stmt->fetch();
    $assert(
        'approved reschedule moves booking to new slot awaiting confirmation',
        $approvedChange['status'] === 'pending'
            && (int) $approvedChange['schedule_id'] === $scheduleIds[1]
            && $approvedChange['request_status'] === 'approved'
    );
    $service->updateTrainerBooking($trainerAccountId, $bookingId, 'confirm');

    $staffBookingsBeforeGrant = $service->getStaffBookings($staffId);
    $assert(
        'Staff has no booking actions before explicit permission',
        $staffBookingsBeforeGrant === []
    );
    try {
        $service->updateBookingAsStaff(
            $staffId,
            $bookingId,
            'cancel',
            'Permission must be required'
        );
        $assert('Staff cannot act without permission', false);
    } catch (BookingException $error) {
        $assert(
            'Staff cannot act without permission',
            $error->errorCode === 'BOOKING_FORBIDDEN'
        );
    }

    $service->updateStaffPermissions($staffId, false, true);
    $staffBookings = $service->getStaffBookings($staffId);
    $assert(
        'Staff booking list reflects granted cancellation permission',
        count(array_filter(
            $staffBookings,
            static fn(array $booking): bool => (int) $booking['booking_id'] === $bookingId
        )) === 1
    );
    $service->updateBookingAsStaff(
        $staffId,
        $bookingId,
        'cancel',
        'Staff cancellation with permission'
    );
    $service->getAdminBookings('cancelled');

    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM booking_audit_log
         WHERE booking_id = :booking_id
           AND actor_member_id = :actor_id
           AND action = 'staff_cancelled'
           AND reason = :reason"
    );
    $stmt->execute([
        'booking_id' => $bookingId,
        'actor_id' => $staffId,
        'reason' => 'Staff cancellation with permission'
    ]);
    $assert('staff action is recorded in the booking audit log', (int) $stmt->fetchColumn() === 1);

    $adminBookingId = $service->createBooking(
        $memberId,
        $scheduleIds[2],
        'Admin action audit test'
    );
    $bookingIds[] = $adminBookingId;
    $service->updateBookingAsAdmin(
        $adminId,
        $adminBookingId,
        'cancel',
        'Admin cancellation audit test'
    );
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM booking_audit_log
         WHERE booking_id = :booking_id
           AND actor_member_id = :actor_id
           AND action = 'admin_cancelled'"
    );
    $stmt->execute([
        'booking_id' => $adminBookingId,
        'actor_id' => $adminId
    ]);
    $assert('admin manual cancellation is audited', (int) $stmt->fetchColumn() === 1);

    foreach ($results as [$name, $passed]) {
        echo ($passed ? 'PASS' : 'FAIL') . ': ' . $name . PHP_EOL;
    }

    $hasFailures = in_array(false, array_column($results, 1), true);
} finally {
    foreach ($bookingIds as $bookingId) {
        foreach (['notification', 'booking_audit_log', 'booking_change_request'] as $table) {
            $stmt = $db->prepare(
                "DELETE FROM {$table} WHERE booking_id = :booking_id"
            );
            $stmt->execute(['booking_id' => $bookingId]);
        }
        $stmt = $db->prepare('DELETE FROM booking WHERE booking_id = :booking_id');
        $stmt->execute(['booking_id' => $bookingId]);
    }

    foreach ($scheduleIds as $scheduleId) {
        $stmt = $db->prepare(
            'DELETE FROM trainer_schedule WHERE schedule_id = :schedule_id'
        );
        $stmt->execute(['schedule_id' => $scheduleId]);
    }

    foreach ($accountIds as $accountId) {
        $stmt = $db->prepare('DELETE FROM notification WHERE member_id = :member_id');
        $stmt->execute(['member_id' => $accountId]);
        $stmt = $db->prepare(
            'UPDATE personal_trainer SET member_id = NULL WHERE member_id = :member_id'
        );
        $stmt->execute(['member_id' => $accountId]);
        $stmt = $db->prepare('DELETE FROM member WHERE member_id = :member_id');
        $stmt->execute(['member_id' => $accountId]);
    }
}

if ($hasFailures) {
    exit(1);
}
