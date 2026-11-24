<?php

require_once __DIR__ . '/../backend/services/BookingService.php';

// Run against a test database containing the demo fixtures from seed.sql.
$db = getDBConnection();
$day = (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->modify('+10 days')->format('Y-m-d');
$slot = $db->prepare('INSERT INTO schedules (pt_id, start_at, end_at) VALUES (4, ?, ?)');
$slot->execute([$day . ' 10:00:00', $day . ' 11:00:00']);
$scheduleId = (int) $db->lastInsertId();
$worker = <<<'PHP'
require 'backend/services/BookingService.php';
while (microtime(true) < (float) $argv[3]) {
    usleep(5000);
}
try {
    (new BookingService())->createBooking(7, (int) $argv[1], '', $argv[2] . 'T17:00', $argv[2] . 'T17:30');
    echo 'held';
} catch (BookingException $error) {
    echo $error->errorCode;
}
PHP;

try {
    $readyAt = (string) (microtime(true) + 1.5);
    $children = [];
    for ($i = 0; $i < 2; $i++) {
        $process = proc_open(
            [PHP_BINARY, '-r', $worker, (string) $scheduleId, $day, $readyAt],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname(__DIR__)
        );
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start parallel booking process.');
        }
        $children[] = [$process, $pipes];
    }

    $results = [];
    $errors = [];
    foreach ($children as [$process, $pipes]) {
        $results[] = trim(stream_get_contents($pipes[1]));
        $stderr = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0 || $stderr !== '') {
            $errors[] = $stderr ?: (string) $exitCode;
        }
    }
    sort($results);
    $count = $db->prepare("SELECT COUNT(*) FROM bookings WHERE schedule_id = ? AND status = 'AwaitingPayment'");
    $count->execute([$scheduleId]);
    if ($errors !== [] || $results !== ['BOOKING_SLOT_UNAVAILABLE', 'held'] || (int) $count->fetchColumn() !== 1) {
        throw new RuntimeException('Expected one hold and one conflict: ' . json_encode([$results, $errors]));
    }
    echo "PASS: parallel bookings create one hold and reject the other request\n";
} finally {
    $db->prepare(
        "DELETE a FROM audit_logs a JOIN bookings b ON b.id = a.entity_id
         WHERE a.entity_type = 'Booking' AND b.schedule_id = ?"
    )->execute([$scheduleId]);
    $db->prepare('DELETE p FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE b.schedule_id = ?')
        ->execute([$scheduleId]);
    $db->prepare('DELETE FROM bookings WHERE schedule_id = ?')->execute([$scheduleId]);
    $db->prepare('DELETE FROM schedules WHERE id = ?')->execute([$scheduleId]);
}
