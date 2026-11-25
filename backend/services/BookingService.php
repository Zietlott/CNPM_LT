<?php

require_once __DIR__ . '/../config/database.php';

class BookingException extends RuntimeException
{
    public int $httpStatus;
    public string $errorCode;

    public function __construct(
        string $message,
        string $errorCode,
        int $httpStatus
    ) {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
    }
}

class BookingService
{
    private const MIN_BOOKING_DURATION_MINUTES = 30;
    private const MAX_BOOKING_DURATION_MINUTES = 180;
    private const MEMBER_CANCELLATION_LIMIT_HOURS = 24;

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
    }

    public function getAvailableTrainers(): array
    {
        $sql = "SELECT DISTINCT t.trainer_id, t.full_name, t.specialty
                FROM personal_trainer t
                INNER JOIN trainer_schedule s
                    ON s.trainer_id = t.trainer_id
                WHERE t.is_active = 1
                  AND s.status = 'available'
                  AND TIMESTAMP(s.work_date, s.start_time) > (UTC_TIMESTAMP() + INTERVAL 7 HOUR)
                  AND NOT EXISTS (
                      SELECT 1
                      FROM booking b
                      INNER JOIN trainer_schedule occupied
                          ON occupied.schedule_id = b.schedule_id
                      WHERE b.status IN ('pending', 'confirmed')
                        AND occupied.trainer_id = s.trainer_id
                        AND occupied.work_date = s.work_date
                        AND occupied.start_time < s.end_time
                        AND occupied.end_time > s.start_time
                  )
                ORDER BY t.full_name";

        return $this->db->query($sql)->fetchAll();
    }

    public function getAvailableSlots(int $trainerId, string $workDate): array
    {
        if ($trainerId <= 0 || !$this->isValidDate($workDate)) {
            throw new BookingException(
                'Huấn luyện viên hoặc ngày tập không hợp lệ.',
                'INVALID_BOOKING_DATA',
                422
            );
        }

        $sql = "SELECT s.schedule_id, s.work_date, s.start_time, s.end_time
                FROM trainer_schedule s
                INNER JOIN personal_trainer t
                    ON t.trainer_id = s.trainer_id
                WHERE s.trainer_id = :trainer_id
                  AND s.work_date = :work_date
                  AND s.work_date >= CURRENT_DATE
                  AND TIMESTAMP(s.work_date, s.start_time) > (UTC_TIMESTAMP() + INTERVAL 7 HOUR)
                  AND s.status = 'available'
                  AND t.is_active = 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM booking b
                      INNER JOIN trainer_schedule occupied
                          ON occupied.schedule_id = b.schedule_id
                      WHERE b.status IN ('pending', 'confirmed')
                        AND occupied.trainer_id = s.trainer_id
                        AND occupied.work_date = s.work_date
                        AND occupied.start_time < s.end_time
                        AND occupied.end_time > s.start_time
                  )
                ORDER BY s.start_time";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'trainer_id' => $trainerId,
            'work_date' => $workDate
        ]);

        return $stmt->fetchAll();
    }

    public function getMemberBookings(int $memberId): array
    {
        $sql = "SELECT
                    b.booking_id,
                    b.status,
                    b.note,
                    b.cancel_reason,
                    b.created_at,
                    t.trainer_id,
                    t.full_name AS trainer_name,
                    t.specialty,
                    s.work_date,
                    s.start_time,
                    s.end_time,
                    cr.request_id AS pending_change_request_id,
                    cr.reason AS pending_change_request_reason,
                    CASE
                        WHEN b.status IN ('pending', 'confirmed')
                         AND TIMESTAMPDIFF(
                             SECOND,
                             (UTC_TIMESTAMP() + INTERVAL 7 HOUR),
                             TIMESTAMP(s.work_date, s.start_time)
                         ) > 0
                        THEN 1
                        ELSE 0
                    END AS can_request_change,
                    CASE
                        WHEN b.status IN ('pending', 'confirmed')
                         AND TIMESTAMPDIFF(
                             SECOND,
                             (UTC_TIMESTAMP() + INTERVAL 7 HOUR),
                             TIMESTAMP(s.work_date, s.start_time)
                         ) >= :cancellation_seconds
                        THEN 1
                        ELSE 0
                    END AS can_cancel
                FROM booking b
                INNER JOIN trainer_schedule s
                    ON s.schedule_id = b.schedule_id
                INNER JOIN personal_trainer t
                    ON t.trainer_id = s.trainer_id
                LEFT JOIN booking_change_request cr
                    ON cr.booking_id = b.booking_id
                   AND cr.status = 'pending'
                WHERE b.member_id = :member_id
                ORDER BY
                    (s.work_date >= CURRENT_DATE) DESC,
                    s.work_date ASC,
                    s.start_time ASC,
                    b.booking_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'cancellation_seconds' => self::MEMBER_CANCELLATION_LIMIT_HOURS * 3600,
            'member_id' => $memberId
        ]);

        return $stmt->fetchAll();
    }

    public function getTrainerBookings(int $memberId): array
    {
        $trainerId = $this->getTrainerIdForAccount($memberId);
        $stmt = $this->db->prepare(
            "SELECT
                b.booking_id,
                b.member_id,
                m.full_name AS member_name,
                m.email AS member_email,
                m.phone AS member_phone,
                bk.duration_minutes,
                bk.fee_snapshot,
                COALESCE(ms.plan_name_snapshot, 'Gói PT tiêu chuẩn') AS package_name,
                b.status,
                b.note,
                b.cancel_reason,
                b.created_at,
                s.schedule_id,
                s.work_date,
                s.start_time,
                s.end_time,
                cr.request_id AS pending_change_request_id,
                cr.reason AS change_request_reason,
                cr.requested_schedule_id,
                requested.work_date AS requested_work_date,
                requested.start_time AS requested_start_time,
                requested.end_time AS requested_end_time
             FROM booking b
             INNER JOIN member m ON m.member_id = b.member_id
             INNER JOIN bookings bk ON bk.id = b.booking_id
             LEFT JOIN memberships ms ON ms.id = bk.membership_id
             INNER JOIN trainer_schedule s ON s.schedule_id = b.schedule_id
             LEFT JOIN booking_change_request cr
                ON cr.booking_id = b.booking_id
               AND cr.status = 'pending'
             LEFT JOIN trainer_schedule requested
                ON requested.schedule_id = cr.requested_schedule_id
             WHERE s.trainer_id = :trainer_id
             ORDER BY
                (s.work_date >= CURRENT_DATE) DESC,
                s.work_date ASC,
                s.start_time ASC,
                b.booking_id DESC"
        );
        $stmt->execute(['trainer_id' => $trainerId]);

        return $stmt->fetchAll();
    }

    public function getAdminBookings(?string $status = null): array
    {
        if (
            $status !== null
            && !in_array($status, ['pending', 'confirmed', 'rejected', 'cancelled', 'completed'], true)
        ) {
            throw new BookingException(
                'Trạng thái booking không hợp lệ.',
                'INVALID_BOOKING_DATA',
                422
            );
        }

        $sql = "SELECT
                    b.booking_id,
                    b.member_id,
                    m.full_name AS member_name,
                    m.email AS member_email,
                    b.status,
                    b.note,
                    b.cancel_reason,
                    b.created_at,
                    t.trainer_id,
                    t.full_name AS trainer_name,
                    s.schedule_id,
                    s.work_date,
                    s.start_time,
                    s.end_time,
                    cr.request_id AS pending_change_request_id,
                    cr.reason AS change_request_reason,
                    requested.work_date AS requested_work_date,
                    requested.start_time AS requested_start_time,
                    requested.end_time AS requested_end_time
                FROM booking b
                INNER JOIN member m ON m.member_id = b.member_id
                INNER JOIN trainer_schedule s ON s.schedule_id = b.schedule_id
                INNER JOIN personal_trainer t ON t.trainer_id = s.trainer_id
                LEFT JOIN booking_change_request cr
                    ON cr.booking_id = b.booking_id
                   AND cr.status = 'pending'
                LEFT JOIN trainer_schedule requested
                    ON requested.schedule_id = cr.requested_schedule_id";

        if ($status !== null) {
            $sql .= ' WHERE b.status = :status';
        }

        $sql .= ' ORDER BY
                    (s.work_date >= CURRENT_DATE) DESC,
                    s.work_date ASC,
                    s.start_time ASC,
                    b.booking_id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($status === null ? [] : ['status' => $status]);

        return $stmt->fetchAll();
    }

    public function getStaffBookings(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                b.booking_id,
                b.member_id,
                m.full_name AS member_name,
                b.status,
                b.note,
                b.cancel_reason,
                b.created_at,
                t.trainer_id,
                t.full_name AS trainer_name,
                s.schedule_id,
                s.work_date,
                s.start_time,
                s.end_time,
                p.can_confirm,
                p.can_cancel
             FROM booking_staff_permission p
             INNER JOIN member staff
                ON staff.member_id = p.member_id
               AND staff.role = 'staff'
               AND staff.status = 'active'
             CROSS JOIN booking b
             INNER JOIN member m ON m.member_id = b.member_id
             INNER JOIN trainer_schedule s ON s.schedule_id = b.schedule_id
             INNER JOIN personal_trainer t ON t.trainer_id = s.trainer_id
             WHERE p.member_id = :member_id
               AND (
                    (p.can_confirm = 1 AND b.status = 'pending')
                    OR (p.can_cancel = 1 AND b.status IN ('pending', 'confirmed'))
               )
             ORDER BY
                (s.work_date >= CURRENT_DATE) DESC,
                s.work_date ASC,
                s.start_time ASC,
                b.booking_id DESC"
        );
        $stmt->execute(['member_id' => $memberId]);

        return $stmt->fetchAll();
    }

    public function getTrainerSchedules(int $memberId): array
    {
        $trainerId = $this->getTrainerIdForAccount($memberId);
        $stmt = $this->db->prepare(
            "SELECT
                s.schedule_id,
                s.work_date,
                s.start_time,
                s.end_time,
                s.status,
                EXISTS (
                    SELECT 1
                    FROM booking b
                    WHERE b.schedule_id = s.schedule_id
                ) AS has_booking
             FROM trainer_schedule s
             WHERE s.trainer_id = :trainer_id
             ORDER BY s.work_date DESC, s.start_time DESC"
        );
        $stmt->execute(['trainer_id' => $trainerId]);

        return $stmt->fetchAll();
    }

    public function getAdminBookingAccessData(): array
    {
        return [
            'trainers' => $this->db->query(
                "SELECT t.trainer_id, t.full_name, t.member_id, t.is_active,
                        m.email AS account_email
                 FROM personal_trainer t
                 LEFT JOIN member m ON m.member_id = t.member_id
                 ORDER BY t.full_name"
            )->fetchAll(),
            'staff' => $this->db->query(
                "SELECT m.member_id, m.full_name, m.email, m.status,
                        COALESCE(p.can_confirm, 0) AS can_confirm,
                        COALESCE(p.can_cancel, 0) AS can_cancel
                 FROM member m
                 LEFT JOIN booking_staff_permission p ON p.member_id = m.member_id
                 WHERE m.role = 'staff'
                 ORDER BY m.full_name"
            )->fetchAll()
        ];
    }

    public function createBooking(
        int $memberId,
        int $scheduleId,
        string $note = ''
    ): int {
        if ($memberId <= 0 || $scheduleId <= 0 || $this->textLength($note) > 500) {
            throw new BookingException(
                'Thông tin đặt lịch không hợp lệ.',
                'INVALID_BOOKING_DATA',
                422
            );
        }

        $this->db->beginTransaction();

        try {
            $memberStmt = $this->db->prepare(
                "SELECT member_id
                 FROM member
                 WHERE member_id = :member_id
                   AND role = 'member'
                   AND status = 'active'
                 FOR UPDATE"
            );
            $memberStmt->execute(['member_id' => $memberId]);

            if (!$memberStmt->fetch()) {
                throw new BookingException(
                    'Tài khoản hội viên không hoạt động.',
                    'MEMBER_NOT_ACTIVE',
                    403
                );
            }

            $trainerIdStmt = $this->db->prepare(
                'SELECT trainer_id
                 FROM trainer_schedule
                 WHERE schedule_id = :schedule_id'
            );
            $trainerIdStmt->execute(['schedule_id' => $scheduleId]);
            $trainerId = $trainerIdStmt->fetchColumn();

            if ($trainerId === false) {
                throw new BookingException(
                    'Khung giờ không tồn tại hoặc không còn khả dụng.',
                    'BOOKING_SLOT_UNAVAILABLE',
                    409
                );
            }

            $trainerStmt = $this->db->prepare(
                "SELECT trainer_id
                 FROM personal_trainer
                 WHERE trainer_id = :trainer_id
                   AND is_active = 1
                 FOR UPDATE"
            );
            $trainerStmt->execute(['trainer_id' => $trainerId]);

            if (!$trainerStmt->fetch()) {
                throw new BookingException(
                    'Huấn luyện viên hiện không hoạt động.',
                    'TRAINER_NOT_AVAILABLE',
                    409
                );
            }

            $scheduleStmt = $this->db->prepare(
                "SELECT schedule_id, trainer_id, work_date, start_time, end_time
                 FROM trainer_schedule
                 WHERE schedule_id = :schedule_id
                   AND trainer_id = :trainer_id
                   AND status = 'available'
                   AND TIMESTAMP(work_date, start_time) > (UTC_TIMESTAMP() + INTERVAL 7 HOUR)
                 FOR UPDATE"
            );
            $scheduleStmt->execute([
                'schedule_id' => $scheduleId,
                'trainer_id' => $trainerId
            ]);
            $schedule = $scheduleStmt->fetch();

            if (!$schedule) {
                throw new BookingException(
                    'Khung giờ vừa được đặt hoặc không còn khả dụng.',
                    'BOOKING_SLOT_UNAVAILABLE',
                    409
                );
            }

            $start = strtotime('1970-01-01 ' . $schedule['start_time']);
            $end = strtotime('1970-01-01 ' . $schedule['end_time']);
            $durationMinutes = (int) (($end - $start) / 60);

            if (
                $durationMinutes < self::MIN_BOOKING_DURATION_MINUTES
                || $durationMinutes > self::MAX_BOOKING_DURATION_MINUTES
            ) {
                throw new BookingException(
                    'Thời lượng buổi tập phải từ 30 đến 180 phút.',
                    'INVALID_BOOKING_DURATION',
                    422
                );
            }

            $membershipStmt = $this->db->prepare(
                "SELECT membership_id
                 FROM membership
                 WHERE member_id = :member_id
                   AND status = 'active'
                   AND start_date <= :start_date
                   AND end_date >= :end_date
                 ORDER BY end_date DESC, membership_id DESC
                 LIMIT 1
                 FOR UPDATE"
            );
            $membershipStmt->execute([
                'member_id' => $memberId,
                'start_date' => $schedule['work_date'],
                'end_date' => $schedule['work_date']
            ]);
            $membershipId = $membershipStmt->fetchColumn();

            if ($membershipId === false) {
                throw new BookingException(
                    'Bạn cần có gói hội viên còn hiệu lực vào ngày tập để đặt lịch.',
                    'MEMBERSHIP_NOT_ACTIVE',
                    409
                );
            }

            $conflictStmt = $this->db->prepare(
                "SELECT b.booking_id
                 FROM booking b
                 INNER JOIN trainer_schedule occupied
                    ON occupied.schedule_id = b.schedule_id
                 WHERE b.status IN ('pending', 'confirmed')
                   AND occupied.work_date = :work_date
                   AND occupied.start_time < :end_time
                   AND occupied.end_time > :start_time
                   AND (
                       occupied.trainer_id = :trainer_id
                       OR b.member_id = :member_id
                   )
                 LIMIT 1
                 FOR UPDATE"
            );
            $conflictStmt->execute([
                'work_date' => $schedule['work_date'],
                'end_time' => $schedule['end_time'],
                'start_time' => $schedule['start_time'],
                'trainer_id' => $trainerId,
                'member_id' => $memberId
            ]);

            if ($conflictStmt->fetch()) {
                throw new BookingException(
                    'Khung giờ vừa được đặt. Vui lòng chọn giờ khác.',
                    'BOOKING_SLOT_UNAVAILABLE',
                    409
                );
            }

            $insertStmt = $this->db->prepare(
                "INSERT INTO bookings
                    (member_id, schedule_id, membership_id, note, status, pt_id,
                     start_at, end_at, duration_minutes, fee_snapshot)
                 SELECT :member_id, s.id, :membership_id, :note, 'Pending', s.pt_id,
                        s.start_at, s.end_at, TIMESTAMPDIFF(MINUTE, s.start_at, s.end_at),
                        ROUND(COALESCE(p.hourly_rate, 0) * TIMESTAMPDIFF(MINUTE, s.start_at, s.end_at) / 60, 2)
                 FROM schedules s JOIN pt_profiles p ON p.user_id = s.pt_id
                 WHERE s.id = :schedule_id"
            );
            $insertStmt->execute([
                'member_id' => $memberId,
                'schedule_id' => $scheduleId,
                'membership_id' => $membershipId,
                'note' => $note !== '' ? $note : null
            ]);
            $bookingId = (int) $this->db->lastInsertId();
            $this->recordBookingAudit(
                $bookingId,
                $memberId,
                'created',
                null,
                null,
                [
                    'schedule_id' => $scheduleId,
                    'membership_id' => (int) $membershipId,
                    'status' => 'pending'
                ]
            );
            $this->db->commit();
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new BookingException(
                    'Khung giờ vừa được đặt. Vui lòng chọn giờ khác.',
                    'BOOKING_SLOT_UNAVAILABLE',
                    409
                );
            }

            throw $e;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }

        $this->createNotification(
            $memberId,
            $bookingId,
            'Đã gửi yêu cầu đặt lịch',
            sprintf(
                'Yêu cầu tập với huấn luyện viên %s lúc %s ngày %s đang chờ xác nhận.',
                $this->getTrainerName((int) $trainerId),
                substr($schedule['start_time'], 0, 5),
                date('d/m/Y', strtotime($schedule['work_date']))
            )
        );

        $trainerAccountId = $this->getTrainerAccountId((int) $trainerId);
        if ($trainerAccountId !== null) {
            $this->createNotification(
                $trainerAccountId,
                $bookingId,
                'Booking mới cần xác nhận',
                sprintf(
                    'Hội viên gửi yêu cầu tập lúc %s ngày %s.',
                    substr($schedule['start_time'], 0, 5),
                    date('d/m/Y', strtotime($schedule['work_date']))
                )
            );
        }

        return $bookingId;
    }

    public function createTrainerSchedule(
        int $memberId,
        string $workDate,
        string $startTime,
        string $endTime
    ): int {
        $this->validateScheduleInput($workDate, $startTime, $endTime);
        $this->db->beginTransaction();

        try {
            $trainerId = $this->lockTrainerForAccount($memberId);
            $this->assertNoScheduleOverlap($trainerId, $workDate, $startTime, $endTime);
            $stmt = $this->db->prepare(
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
            $scheduleId = (int) $this->db->lastInsertId();
            $this->db->commit();

            return $scheduleId;
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }
    }

    public function createTrainerScheduleBatch(
        int $memberId,
        array $slots
    ): array {
        $created = [];
        $errors = [];
        foreach ($slots as $slot) {
            $workDate = trim((string)($slot['work_date'] ?? ''));
            $startTime = trim((string)($slot['start_time'] ?? ''));
            $endTime = trim((string)($slot['end_time'] ?? ''));
            if ($workDate === '' || $startTime === '' || $endTime === '') {
                continue;
            }
            try {
                $id = $this->createTrainerSchedule($memberId, $workDate, $startTime, $endTime);
                $created[] = [
                    'schedule_id' => $id,
                    'work_date' => $workDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime
                ];
            } catch (Throwable $e) {
                $errors[] = [
                    'work_date' => $workDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'message' => $e->getMessage()
                ];
            }
        }
        return [
            'created' => $created,
            'errors' => $errors,
            'total_created' => count($created)
        ];
    }

    public function updateTrainerSchedule(
        int $memberId,
        int $scheduleId,
        string $workDate,
        string $startTime,
        string $endTime
    ): void {
        if ($scheduleId <= 0) {
            throw $this->invalidBookingData('Khung giờ không hợp lệ.');
        }

        $this->validateScheduleInput($workDate, $startTime, $endTime);
        $this->db->beginTransaction();

        try {
            $trainerId = $this->lockTrainerForAccount($memberId);
            $stmt = $this->db->prepare(
                'SELECT schedule_id, status
                 FROM trainer_schedule
                 WHERE schedule_id = :schedule_id
                   AND trainer_id = :trainer_id
                 FOR UPDATE'
            );
            $stmt->execute([
                'schedule_id' => $scheduleId,
                'trainer_id' => $trainerId
            ]);
            $schedule = $stmt->fetch();

            if (!$schedule) {
                throw new BookingException(
                    'Không tìm thấy khung giờ của bạn.',
                    'TRAINER_SCHEDULE_NOT_FOUND',
                    404
                );
            }

            if ($schedule['status'] !== 'available' || $this->scheduleHasBookings($scheduleId)) {
                throw new BookingException(
                    'Không thể sửa khung giờ đã có booking hoặc đã bị khóa.',
                    'TRAINER_SCHEDULE_IN_USE',
                    409
                );
            }

            $this->assertNoScheduleOverlap(
                $trainerId,
                $workDate,
                $startTime,
                $endTime,
                $scheduleId
            );
            $stmt = $this->db->prepare(
                'UPDATE schedules s CROSS JOIN (SELECT :work_date AS work_date) d
                 SET s.start_at = TIMESTAMP(d.work_date, :start_time) - INTERVAL 7 HOUR,
                     s.end_at = TIMESTAMP(d.work_date, :end_time) - INTERVAL 7 HOUR
                 WHERE s.id = :schedule_id'
            );
            $stmt->execute([
                'work_date' => $workDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'schedule_id' => $scheduleId
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }
    }

    public function deactivateTrainerSchedule(int $memberId, int $scheduleId): void
    {
        if ($scheduleId <= 0) {
            throw $this->invalidBookingData('Khung giờ không hợp lệ.');
        }

        $this->db->beginTransaction();

        try {
            $trainerId = $this->lockTrainerForAccount($memberId);
            $stmt = $this->db->prepare(
                'SELECT schedule_id, status
                 FROM trainer_schedule
                 WHERE schedule_id = :schedule_id
                   AND trainer_id = :trainer_id
                 FOR UPDATE'
            );
            $stmt->execute([
                'schedule_id' => $scheduleId,
                'trainer_id' => $trainerId
            ]);
            $schedule = $stmt->fetch();

            if (!$schedule) {
                throw new BookingException(
                    'Không tìm thấy khung giờ của bạn.',
                    'TRAINER_SCHEDULE_NOT_FOUND',
                    404
                );
            }

            if ($this->scheduleHasBookings($scheduleId)) {
                throw new BookingException(
                    'Không thể khóa khung giờ đã phát sinh booking.',
                    'TRAINER_SCHEDULE_IN_USE',
                    409
                );
            }

            if ($schedule['status'] === 'available') {
                $stmt = $this->db->prepare(
                    "UPDATE schedules SET status = 'Closed' WHERE id = :schedule_id"
                );
                $stmt->execute(['schedule_id' => $scheduleId]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }
    }

    public function deleteTrainerSchedule(int $memberId, int $scheduleId): void
    {
        if ($scheduleId <= 0) {
            throw $this->invalidBookingData('Khung giờ không hợp lệ.');
        }

        $this->db->beginTransaction();

        try {
            $trainerId = $this->lockTrainerForAccount($memberId);
            $stmt = $this->db->prepare(
                'SELECT schedule_id, status
                 FROM trainer_schedule
                 WHERE schedule_id = :schedule_id
                   AND trainer_id = :trainer_id
                 FOR UPDATE'
            );
            $stmt->execute([
                'schedule_id' => $scheduleId,
                'trainer_id' => $trainerId
            ]);
            $schedule = $stmt->fetch();

            if (!$schedule) {
                throw new BookingException(
                    'Không tìm thấy khung giờ của bạn.',
                    'TRAINER_SCHEDULE_NOT_FOUND',
                    404
                );
            }

            if ($this->scheduleHasBookings($scheduleId)) {
                throw new BookingException(
                    'Không thể xóa khung giờ đã có booking.',
                    'TRAINER_SCHEDULE_IN_USE',
                    409
                );
            }

            $stmt = $this->db->prepare(
                "DELETE FROM schedules WHERE id = :schedule_id AND pt_id = :trainer_id"
            );
            $stmt->execute(['schedule_id' => $scheduleId, 'trainer_id' => $trainerId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }
    }

    public function updateTrainerBooking(
        int $memberId,
        int $bookingId,
        string $action,
        string $reason = ''
    ): void {
        if (
            $bookingId <= 0
            || !in_array($action, ['confirm', 'reject', 'complete', 'cancel'], true)
            || $this->textLength($reason) > 255
        ) {
            throw $this->invalidBookingData('Thao tác booking không hợp lệ.');
        }

        if (($action === 'reject' || $action === 'cancel') && trim($reason) === '') {
            throw $this->invalidBookingData('Vui lòng nhập lý do ' . ($action === 'reject' ? 'từ chối.' : 'hủy lịch.'));
        }

        $this->db->beginTransaction();

        try {
            $this->lockMember($memberId);
            $trainerId = $this->lockTrainerForAccount($memberId);
            $booking = $this->lockBooking($bookingId);

            if ((int) $booking['trainer_id'] !== $trainerId) {
                throw new BookingException(
                    'Bạn không có quyền xử lý booking này.',
                    'BOOKING_FORBIDDEN',
                    403
                );
            }

            $this->applyBookingTransition(
                $booking,
                $memberId,
                $action,
                $reason,
                'trainer'
            );
            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }

        $this->notifyBookingTransition($booking, $action, $reason);
    }

    public function createChangeRequest(
        int $memberId,
        int $bookingId,
        int $requestedScheduleId,
        string $reason
    ): int {
        if (
            $bookingId <= 0
            || $requestedScheduleId <= 0
            || trim($reason) === ''
            || $this->textLength($reason) > 255
        ) {
            throw $this->invalidBookingData(
                'Vui lòng chọn khung giờ mới và nhập lý do đổi lịch.'
            );
        }

        $this->db->beginTransaction();

        try {
            $this->lockMember($memberId);
            $booking = $this->lockBooking($bookingId);

            if ((int) $booking['member_id'] !== $memberId) {
                throw new BookingException(
                    'Không tìm thấy booking của bạn.',
                    'BOOKING_NOT_FOUND',
                    404
                );
            }

            if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
                throw new BookingException(
                    'Chỉ có thể yêu cầu đổi lịch cho booking đang chờ hoặc đã xác nhận.',
                    'BOOKING_STATE_INVALID',
                    409
                );
            }

            if ($this->bookingHasStarted($booking)) {
                throw new BookingException(
                    'Không thể đổi lịch sau khi buổi tập đã bắt đầu.',
                    'BOOKING_CHANGE_DEADLINE_PASSED',
                    409
                );
            }

            $this->lockTrainer($booking['trainer_id']);
            $this->assertNoPendingChangeRequest($bookingId);
            $newSchedule = $this->lockAvailableSchedule($requestedScheduleId);

            if ((int) $newSchedule['trainer_id'] !== (int) $booking['trainer_id']) {
                throw new BookingException(
                    'Yêu cầu đổi lịch cần được PT hiện tại duyệt; vui lòng chọn lịch của PT này.',
                    'TRAINER_NOT_AVAILABLE',
                    409
                );
            }

            if ((int) $newSchedule['schedule_id'] === (int) $booking['schedule_id']) {
                throw $this->invalidBookingData('Khung giờ mới phải khác khung giờ hiện tại.');
            }

            $this->validateMembershipForDate(
                (int) $booking['member_id'],
                $newSchedule['work_date']
            );
            $this->assertNoActiveBookingConflict(
                (int) $booking['member_id'],
                (int) $booking['trainer_id'],
                $newSchedule,
                $bookingId
            );

            $stmt = $this->db->prepare(
                "INSERT INTO booking_change_requests
                    (booking_id, requested_by, old_schedule_id, new_schedule_id, new_start_at, new_end_at, reason)
                 SELECT b.id, :member_id, b.schedule_id, s.id, s.start_at, s.end_at, :reason
                 FROM bookings b CROSS JOIN schedules s
                 WHERE b.id = :booking_id AND s.id = :schedule_id"
            );
            $stmt->execute([
                'booking_id' => $bookingId,
                'member_id' => $memberId,
                'schedule_id' => $requestedScheduleId,
                'reason' => trim($reason)
            ]);
            $requestId = (int) $this->db->lastInsertId();

            $this->recordBookingAudit(
                $bookingId,
                $memberId,
                'change_requested',
                trim($reason),
                $this->bookingSnapshot($booking),
                [
                    'request_id' => $requestId,
                    'requested_schedule_id' => $requestedScheduleId
                ]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }

        $trainerAccountId = $this->getTrainerAccountId((int) $booking['trainer_id']);
        if ($trainerAccountId !== null) {
            $this->createNotification(
                $trainerAccountId,
                $bookingId,
                'Yêu cầu đổi lịch mới',
                'Hội viên gửi yêu cầu đổi lịch. Vui lòng kiểm tra và xử lý.'
            );
        }

        return $requestId;
    }

    public function cancelChangeRequest(
        int $memberId,
        int $bookingId,
        int $requestId
    ): void {
        $this->db->beginTransaction();

        try {
            $this->lockMember($memberId);
            $booking = $this->lockBooking($bookingId);

            if ((int) $booking['member_id'] !== $memberId) {
                throw new BookingException(
                    'Không tìm thấy booking của bạn.',
                    'BOOKING_NOT_FOUND',
                    404
                );
            }

            $stmt = $this->db->prepare(
                "UPDATE booking_change_requests
                 SET status = 'Withdrawn'
                 WHERE id = :request_id
                   AND booking_id = :booking_id
                   AND requested_by = :member_id
                   AND status = 'pending'"
            );
            $stmt->execute([
                'request_id' => $requestId,
                'booking_id' => $bookingId,
                'member_id' => $memberId
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new BookingException(
                    'Không tìm thấy yêu cầu đổi lịch đang chờ.',
                    'BOOKING_CHANGE_REQUEST_NOT_FOUND',
                    404
                );
            }

            $this->recordBookingAudit(
                $bookingId,
                $memberId,
                'change_request_cancelled',
                null,
                ['request_id' => $requestId, 'status' => 'pending'],
                ['request_id' => $requestId, 'status' => 'cancelled']
            );
            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }
    }

    public function reviewChangeRequest(
        int $memberId,
        int $bookingId,
        int $requestId,
        bool $approve,
        string $decisionReason = ''
    ): void {
        if (
            $bookingId <= 0
            || $requestId <= 0
            || $this->textLength($decisionReason) > 255
            || (!$approve && trim($decisionReason) === '')
        ) {
            throw $this->invalidBookingData(
                'Thông tin xử lý yêu cầu đổi lịch không hợp lệ.'
            );
        }

        $this->db->beginTransaction();

        try {
            $this->lockMember($memberId);
            $trainerId = $this->lockTrainerForAccount($memberId);
            $booking = $this->lockBooking($bookingId);

            if ((int) $booking['trainer_id'] !== $trainerId) {
                throw new BookingException(
                    'Bạn không có quyền xử lý yêu cầu này.',
                    'BOOKING_FORBIDDEN',
                    403
                );
            }

            if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
                throw new BookingException(
                    'Booking hiện không thể đổi lịch.',
                    'BOOKING_STATE_INVALID',
                    409
                );
            }

            if ($this->bookingHasStarted($booking)) {
                throw new BookingException(
                    'Yêu cầu đổi lịch đã hết hạn vì buổi tập đã bắt đầu.',
                    'BOOKING_CHANGE_DEADLINE_PASSED',
                    409
                );
            }

            $stmt = $this->db->prepare(
                "SELECT *
                 FROM booking_change_request
                 WHERE request_id = :request_id
                   AND booking_id = :booking_id
                   AND status = 'pending'
                 FOR UPDATE"
            );
            $stmt->execute([
                'request_id' => $requestId,
                'booking_id' => $bookingId
            ]);
            $changeRequest = $stmt->fetch();

            if (!$changeRequest) {
                throw new BookingException(
                    'Không tìm thấy yêu cầu đổi lịch đang chờ.',
                    'BOOKING_CHANGE_REQUEST_NOT_FOUND',
                    404
                );
            }

            $requestedSchedule = null;
            if ($approve) {
                $requestedSchedule = $this->lockAvailableSchedule(
                    (int) $changeRequest['requested_schedule_id']
                );
                if ((int) $requestedSchedule['trainer_id'] !== $trainerId) {
                    throw new BookingException(
                        'Khung giờ yêu cầu không còn thuộc lịch của bạn.',
                        'TRAINER_NOT_AVAILABLE',
                        409
                    );
                }

                $this->validateMembershipForDate(
                    (int) $booking['member_id'],
                    $requestedSchedule['work_date']
                );
                $this->assertNoActiveBookingConflict(
                    (int) $booking['member_id'],
                    $trainerId,
                    $requestedSchedule,
                    $bookingId
                );

                $before = $this->bookingSnapshot($booking);
                $stmt = $this->db->prepare(
                    "UPDATE bookings b JOIN schedules s ON s.id = :schedule_id
                     SET b.schedule_id = s.id, b.start_at = s.start_at, b.end_at = s.end_at,
                         b.duration_minutes = TIMESTAMPDIFF(MINUTE, s.start_at, s.end_at),
                         b.status = 'Pending', b.cancel_reason = NULL
                     WHERE b.id = :booking_id"
                );
                $stmt->execute([
                    'schedule_id' => $requestedSchedule['schedule_id'],
                    'booking_id' => $bookingId
                ]);
                $this->recordBookingAudit(
                    $bookingId,
                    $memberId,
                    'change_approved',
                    trim($decisionReason) !== '' ? trim($decisionReason) : null,
                    $before,
                    [
                        'schedule_id' => (int) $requestedSchedule['schedule_id'],
                        'status' => 'pending'
                    ]
                );
            } else {
                $this->recordBookingAudit(
                    $bookingId,
                    $memberId,
                    'change_rejected',
                    trim($decisionReason),
                    ['request_id' => $requestId, 'status' => 'pending'],
                    ['request_id' => $requestId, 'status' => 'rejected']
                );
            }

            $stmt = $this->db->prepare(
                "UPDATE booking_change_requests
                 SET status = :status,
                     reviewed_by = :reviewer_id,
                     reviewed_at = UTC_TIMESTAMP(),
                     reject_reason = :decision_reason
                 WHERE id = :request_id"
            );
            $stmt->execute([
                'status' => $approve ? 'approved' : 'rejected',
                'reviewer_id' => $memberId,
                'decision_reason' => trim($decisionReason) !== ''
                    ? trim($decisionReason)
                    : null,
                'request_id' => $requestId
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }

        if ($approve && $requestedSchedule !== null) {
            $this->createNotification(
                (int) $booking['member_id'],
                $bookingId,
                'Yêu cầu đổi lịch được duyệt',
                'Lịch tập mới đã được cập nhật và đang chờ PT xác nhận lại.'
            );
        } else {
            $this->createNotification(
                (int) $booking['member_id'],
                $bookingId,
                'Yêu cầu đổi lịch bị từ chối',
                'PT đã từ chối yêu cầu đổi lịch. Booking ban đầu vẫn được giữ nguyên.'
            );
        }
    }

    public function updateBookingAsAdmin(
        int $actorMemberId,
        int $bookingId,
        string $action,
        string $reason,
        string $actorRole = 'admin'
    ): void {
        if (
            $bookingId <= 0
            || !in_array($action, ['confirm', 'cancel'], true)
            || trim($reason) === ''
            || $this->textLength($reason) > 255
            || !in_array($actorRole, ['admin', 'staff'], true)
        ) {
            throw $this->invalidBookingData(
                'Vui lòng chọn thao tác và nhập lý do hợp lệ.'
            );
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT member_id, role, status
                 FROM member
                 WHERE member_id = :member_id
                 FOR UPDATE'
            );
            $stmt->execute(['member_id' => $actorMemberId]);
            $actor = $stmt->fetch();
            if (
                !$actor
                || $actor['status'] !== 'active'
                || $actor['role'] !== $actorRole
            ) {
                throw new BookingException(
                    'Tài khoản không có quyền thao tác booking.',
                    'BOOKING_FORBIDDEN',
                    403
                );
            }

            if ($actorRole === 'staff') {
                $permission = $action === 'confirm' ? 'can_confirm' : 'can_cancel';
                $stmt = $this->db->prepare(
                    "SELECT {$permission}
                     FROM booking_staff_permission
                     WHERE member_id = :member_id
                     FOR UPDATE"
                );
                $stmt->execute(['member_id' => $actorMemberId]);
                if ((int) $stmt->fetchColumn() !== 1) {
                    throw new BookingException(
                        'Admin chưa cấp quyền thao tác booking này cho bạn.',
                        'BOOKING_FORBIDDEN',
                        403
                    );
                }
            }

            $booking = $this->lockBooking($bookingId);

            if ($action === 'confirm') {
                $this->applyBookingTransition(
                    $booking,
                    $actorMemberId,
                    'confirm',
                    trim($reason),
                    $actorRole
                );
            } else {
                if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
                    throw new BookingException(
                        'Chỉ có thể hủy booking đang chờ hoặc đã xác nhận.',
                        'BOOKING_STATE_INVALID',
                        409
                    );
                }

                $stmt = $this->db->prepare(
                    "UPDATE bookings
                     SET status = 'cancelled',
                         cancel_reason = :reason
                     WHERE id = :booking_id"
                );
                $stmt->execute([
                    'reason' => trim($reason),
                    'booking_id' => $bookingId
                ]);
                $this->cancelPendingChangeRequests($bookingId);
                $this->recordBookingAudit(
                    $bookingId,
                    $actorMemberId,
                    $actorRole . '_cancelled',
                    trim($reason),
                    $this->bookingSnapshot($booking),
                    ['status' => 'cancelled', 'cancel_reason' => trim($reason)]
                );
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }

        $this->notifyBookingTransition($booking, $action, $reason);
    }

    public function updateBookingAsStaff(
        int $actorMemberId,
        int $bookingId,
        string $action,
        string $reason
    ): void {
        if (!in_array($action, ['confirm', 'cancel'], true)) {
            throw $this->invalidBookingData('Thao tác booking không hợp lệ.');
        }

        $this->updateBookingAsAdmin(
            $actorMemberId,
            $bookingId,
            $action,
            $reason,
            'staff'
        );
    }

    public function createTrainerAccount(
        int $trainerId,
        string $email,
        string $phone,
        string $password
    ): int {
        if (
            $trainerId <= 0
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || trim($phone) === ''
            || strlen($password) < 8
        ) {
            throw $this->invalidBookingData(
                'Email, số điện thoại hoặc mật khẩu tài khoản PT không hợp lệ.'
            );
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT trainer_id, member_id, full_name, is_active
                 FROM personal_trainer
                 WHERE trainer_id = :trainer_id
                 FOR UPDATE'
            );
            $stmt->execute(['trainer_id' => $trainerId]);
            $trainer = $stmt->fetch();
            if (!$trainer) {
                throw new BookingException(
                    'Không tìm thấy huấn luyện viên.',
                    'TRAINER_NOT_FOUND',
                    404
                );
            }
            if ((int) $trainer['is_active'] !== 1) {
                throw new BookingException(
                    'Không thể tạo tài khoản cho huấn luyện viên không hoạt động.',
                    'TRAINER_NOT_AVAILABLE',
                    409
                );
            }
            if ($trainer['member_id'] !== null) {
                throw new BookingException(
                    'Huấn luyện viên này đã có tài khoản đăng nhập.',
                    'TRAINER_ACCOUNT_EXISTS',
                    409
                );
            }

            $stmt = $this->db->prepare(
                'UPDATE users SET email = :email, phone = :phone, password_hash = :password_hash
                 WHERE id = :id AND email IS NULL'
            );
            $stmt->execute([
                'email' => strtolower(trim($email)),
                'phone' => trim($phone),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $trainerId
            ]);
            if ($stmt->rowCount() !== 1) {
                throw new BookingException('Huấn luyện viên đã có tài khoản.', 'TRAINER_ACCOUNT_EXISTS', 409);
            }
            $this->db->commit();

            return $trainerId;
        } catch (PDOException $e) {
            $this->rollBackIfActive();
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new BookingException(
                    'Email hoặc số điện thoại đã được sử dụng.',
                    'ACCOUNT_ALREADY_EXISTS',
                    409
                );
            }
            throw $e;
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }
    }

    public function createStaffAccount(
        string $fullName,
        string $email,
        string $phone,
        string $password,
        bool $canConfirm,
        bool $canCancel,
        int $actorId
    ): int {
        if (
            trim($fullName) === ''
            || $this->textLength($fullName) > 100
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || trim($phone) === ''
            || strlen($password) < 8
        ) {
            throw $this->invalidBookingData(
                'Thông tin tài khoản Staff không hợp lệ.'
            );
        }

        $this->db->beginTransaction();

        try {
            $memberId = $this->insertAccount(
                trim($fullName),
                strtolower(trim($email)),
                trim($phone),
                $password,
                'Staff'
            );
            $this->saveStaffPermissions($memberId, $canConfirm, $canCancel, $actorId);
            $this->db->commit();

            return $memberId;
        } catch (PDOException $e) {
            $this->rollBackIfActive();
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new BookingException(
                    'Email hoặc số điện thoại đã được sử dụng.',
                    'ACCOUNT_ALREADY_EXISTS',
                    409
                );
            }
            throw $e;
        } catch (Throwable $e) {
            $this->rollBackIfActive();
            throw $e;
        }
    }

    public function updateStaffPermissions(
        int $staffId,
        bool $canConfirm,
        bool $canCancel,
        int $actorId
    ): void {
        if ($staffId <= 0) {
            throw $this->invalidBookingData('Tài khoản Staff không hợp lệ.');
        }

        $stmt = $this->db->prepare(
            "SELECT member_id FROM member WHERE member_id = :member_id AND role = 'staff'"
        );
        $stmt->execute(['member_id' => $staffId]);
        if (!$stmt->fetch()) {
            throw new BookingException(
                'Không tìm thấy tài khoản Staff.',
                'STAFF_NOT_FOUND',
                404
            );
        }

        $this->saveStaffPermissions($staffId, $canConfirm, $canCancel, $actorId);
    }

    private function applyBookingTransition(
        array $booking,
        int $actorMemberId,
        string $action,
        string $reason,
        string $actorRole
    ): void {
        $bookingId = (int) $booking['booking_id'];
        $before = $this->bookingSnapshot($booking);

        if ($action === 'confirm') {
            if ($booking['status'] !== 'pending') {
                throw new BookingException(
                    'Chỉ có thể xác nhận booking đang chờ.',
                    'BOOKING_STATE_INVALID',
                    409
                );
            }

            $this->validateBookingForConfirmation($booking);
            $stmt = $this->db->prepare(
                "UPDATE bookings
                 SET status = 'confirmed',
                     cancel_reason = NULL
                 WHERE id = :booking_id"
            );
            $stmt->execute(['booking_id' => $bookingId]);

            $this->lockTrainer((int) $booking['trainer_id']);
            $this->recordBookingAudit(
                $bookingId,
                $actorMemberId,
                $actorRole . '_confirmed',
                trim($reason) !== '' ? trim($reason) : null,
                $before,
                ['status' => 'confirmed']
            );
            return;
        }

        if ($action === 'reject') {
            if ($booking['status'] !== 'pending') {
                throw new BookingException(
                    'Chỉ có thể từ chối booking đang chờ.',
                    'BOOKING_STATE_INVALID',
                    409
                );
            }

            $stmt = $this->db->prepare(
                "UPDATE bookings
                 SET status = 'rejected',
                     cancel_reason = :reason
                 WHERE id = :booking_id"
            );
            $stmt->execute([
                'reason' => trim($reason),
                'booking_id' => $bookingId
            ]);
            $this->cancelPendingChangeRequests($bookingId);
            $this->recordBookingAudit(
                $bookingId,
                $actorMemberId,
                'trainer_rejected',
                trim($reason),
                $before,
                ['status' => 'rejected', 'cancel_reason' => trim($reason)]
            );
            return;
        }

        if ($action === 'complete') {
            if ($booking['status'] !== 'confirmed') {
                throw new BookingException(
                    'Chỉ có thể hoàn tất booking đã xác nhận.',
                    'BOOKING_STATE_INVALID',
                    409
                );
            }

            $stmt = $this->db->prepare(
                "UPDATE bookings
                 SET status = 'completed'
                 WHERE id = :booking_id"
            );
            $stmt->execute(['booking_id' => $bookingId]);
            $this->recordBookingAudit(
                $bookingId,
                $actorMemberId,
                'trainer_completed',
                null,
                $before,
                ['status' => 'completed']
            );

            // Tự động ghi nhận thu nhập PT khi booking hoàn tất (mặc định Chờ thanh toán)
            $earningStmt = $this->db->prepare(
                "INSERT IGNORE INTO pt_earnings (pt_id, booking_id, amount, payment_status, recorded_at)
                 SELECT pt_id, id, fee_snapshot, 'Pending', NOW()
                 FROM bookings
                 WHERE id = :booking_id
                   AND fee_snapshot > 0"
            );
            $earningStmt->execute(['booking_id' => $bookingId]);
            return;
        }

        if ($action === 'cancel') {
            if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
                throw new BookingException(
                    'Chỉ có thể hủy booking đang chờ hoặc đã xác nhận.',
                    'BOOKING_STATE_INVALID',
                    409
                );
            }

            $stmt = $this->db->prepare(
                "UPDATE bookings
                 SET status = 'cancelled',
                     cancel_reason = :reason
                 WHERE id = :booking_id"
            );
            $stmt->execute([
                'reason' => trim($reason),
                'booking_id' => $bookingId
            ]);
            $this->cancelPendingChangeRequests($bookingId);
            $this->recordBookingAudit(
                $bookingId,
                $actorMemberId,
                'trainer_cancelled',
                trim($reason),
                $before,
                ['status' => 'cancelled', 'cancel_reason' => trim($reason)]
            );
            return;
        }
    }

    private function validateBookingForConfirmation(array $booking): void
    {
        if ((int) $booking['trainer_is_active'] !== 1) {
            throw new BookingException(
                'Huấn luyện viên hiện không hoạt động.',
                'TRAINER_NOT_AVAILABLE',
                409
            );
        }

        if (
            $booking['schedule_status'] !== 'available'
            || (int) $booking['seconds_until_start'] <= 0
        ) {
            throw new BookingException(
                'Khung giờ không còn hợp lệ hoặc đã qua.',
                'TRAINER_NOT_AVAILABLE',
                409
            );
        }

        $this->validateDuration($booking['start_time'], $booking['end_time']);
        $this->validateMembershipForDate(
            (int) $booking['member_id'],
            $booking['work_date']
        );
        $this->assertNoActiveBookingConflict(
            (int) $booking['member_id'],
            (int) $booking['trainer_id'],
            $booking,
            (int) $booking['booking_id']
        );
    }

    private function validateScheduleInput(
        string $workDate,
        string $startTime,
        string $endTime
    ): void {
        if (
            !$this->isValidDate($workDate)
            || !$this->isValidTime($startTime)
            || !$this->isValidTime($endTime)
            || strtotime('1970-01-01 ' . $endTime)
                <= strtotime('1970-01-01 ' . $startTime)
        ) {
            throw $this->invalidBookingData('Ngày hoặc giờ làm việc không hợp lệ.');
        }

        $this->validateDuration($startTime, $endTime);
        $stmt = $this->db->prepare(
            'SELECT TIMESTAMPDIFF(
                SECOND,
                (UTC_TIMESTAMP() + INTERVAL 7 HOUR),
                TIMESTAMP(:work_date, :start_time)
             )'
        );
        $stmt->execute([
            'work_date' => $workDate,
            'start_time' => $startTime
        ]);
        if ((int) $stmt->fetchColumn() <= 0) {
            throw new BookingException(
                'Không thể tạo lịch làm việc trong quá khứ.',
                'BOOKING_IN_PAST',
                422
            );
        }
    }

    private function validateDuration(string $startTime, string $endTime): void
    {
        $duration = (
            strtotime('1970-01-01 ' . $endTime)
            - strtotime('1970-01-01 ' . $startTime)
        ) / 60;

        if (
            $duration < self::MIN_BOOKING_DURATION_MINUTES
            || $duration > self::MAX_BOOKING_DURATION_MINUTES
        ) {
            throw new BookingException(
                'Thời lượng buổi tập phải từ 30 đến 180 phút.',
                'INVALID_BOOKING_DURATION',
                422
            );
        }
    }

    private function assertNoScheduleOverlap(
        int $trainerId,
        string $workDate,
        string $startTime,
        string $endTime,
        ?int $excludeScheduleId = null
    ): void {
        $sql = "SELECT schedule_id
                FROM trainer_schedule
                WHERE trainer_id = :trainer_id
                  AND work_date = :work_date
                  AND start_time < :end_time
                  AND end_time > :start_time";
        $params = [
            'trainer_id' => $trainerId,
            'work_date' => $workDate,
            'end_time' => $endTime,
            'start_time' => $startTime
        ];

        if ($excludeScheduleId !== null) {
            $sql .= ' AND schedule_id <> :exclude_schedule_id';
            $params['exclude_schedule_id'] = $excludeScheduleId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1 FOR UPDATE');
        $stmt->execute($params);
        if ($stmt->fetch()) {
            throw new BookingException(
                'Khung giờ làm việc bị trùng với lịch khác của bạn.',
                'TRAINER_SCHEDULE_CONFLICT',
                409
            );
        }
    }

    private function assertNoActiveBookingConflict(
        int $memberId,
        int $trainerId,
        array $schedule,
        ?int $excludeBookingId = null
    ): void {
        $sql = "SELECT b.booking_id
                FROM booking b
                INNER JOIN trainer_schedule occupied
                    ON occupied.schedule_id = b.schedule_id
                WHERE b.status IN ('pending', 'confirmed')
                  AND occupied.work_date = :work_date
                  AND occupied.start_time < :end_time
                  AND occupied.end_time > :start_time
                  AND (
                      occupied.trainer_id = :trainer_id
                      OR b.member_id = :member_id
                  )";
        $params = [
            'work_date' => $schedule['work_date'],
            'end_time' => $schedule['end_time'],
            'start_time' => $schedule['start_time'],
            'trainer_id' => $trainerId,
            'member_id' => $memberId
        ];

        if ($excludeBookingId !== null) {
            $sql .= ' AND b.booking_id <> :exclude_booking_id';
            $params['exclude_booking_id'] = $excludeBookingId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1 FOR UPDATE');
        $stmt->execute($params);
        if ($stmt->fetch()) {
            throw new BookingException(
                'Khung giờ vừa được giữ bởi booking khác. Vui lòng chọn giờ khác.',
                'BOOKING_SLOT_UNAVAILABLE',
                409
            );
        }
    }

    private function validateMembershipForDate(int $memberId, string $workDate): int
    {
        $stmt = $this->db->prepare(
            "SELECT membership_id
             FROM membership
             WHERE member_id = :member_id
               AND status = 'active'
               AND start_date <= :start_date
               AND end_date >= :end_date
             ORDER BY end_date DESC, membership_id DESC
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([
            'member_id' => $memberId,
            'start_date' => $workDate,
            'end_date' => $workDate
        ]);
        $membershipId = $stmt->fetchColumn();

        if ($membershipId === false) {
            throw new BookingException(
                'Membership của hội viên không còn hiệu lực vào ngày tập.',
                'MEMBERSHIP_NOT_ACTIVE',
                409
            );
        }

        return (int) $membershipId;
    }

    private function lockAvailableSchedule(int $scheduleId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                s.schedule_id,
                s.trainer_id,
                s.work_date,
                s.start_time,
                s.end_time,
                s.status,
                t.is_active AS trainer_is_active
             FROM trainer_schedule s
             INNER JOIN personal_trainer t ON t.trainer_id = s.trainer_id
             WHERE s.schedule_id = :schedule_id
               AND s.status = 'available'
               AND t.is_active = 1
               AND TIMESTAMP(s.work_date, s.start_time) > (UTC_TIMESTAMP() + INTERVAL 7 HOUR)
             FOR UPDATE"
        );
        $stmt->execute(['schedule_id' => $scheduleId]);
        $schedule = $stmt->fetch();

        if (!$schedule) {
            throw new BookingException(
                'Khung giờ vừa được đặt hoặc không còn khả dụng.',
                'BOOKING_SLOT_UNAVAILABLE',
                409
            );
        }

        $this->validateDuration($schedule['start_time'], $schedule['end_time']);

        return $schedule;
    }

    private function lockBooking(int $bookingId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                b.booking_id,
                b.member_id,
                b.schedule_id,
                b.membership_id,
                b.status,
                b.note,
                b.cancel_reason,
                t.trainer_id,
                t.is_active AS trainer_is_active,
                t.member_id AS trainer_member_id,
                s.work_date,
                s.start_time,
                s.end_time,
                s.status AS schedule_status,
                TIMESTAMPDIFF(
                    SECOND,
                    (UTC_TIMESTAMP() + INTERVAL 7 HOUR),
                    TIMESTAMP(s.work_date, s.start_time)
                ) AS seconds_until_start,
                TIMESTAMPDIFF(
                    SECOND,
                    (UTC_TIMESTAMP() + INTERVAL 7 HOUR),
                    TIMESTAMP(s.work_date, s.end_time)
                ) AS seconds_until_end
             FROM booking b
             INNER JOIN trainer_schedule s ON s.schedule_id = b.schedule_id
             INNER JOIN personal_trainer t ON t.trainer_id = s.trainer_id
             WHERE b.booking_id = :booking_id
             FOR UPDATE'
        );
        $stmt->execute(['booking_id' => $bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            throw new BookingException(
                'Không tìm thấy booking.',
                'BOOKING_NOT_FOUND',
                404
            );
        }

        return $booking;
    }

    private function lockMember(int $memberId): void
    {
        $stmt = $this->db->prepare(
            "SELECT member_id
             FROM member
             WHERE member_id = :member_id
               AND status = 'active'
             FOR UPDATE"
        );
        $stmt->execute(['member_id' => $memberId]);
        if (!$stmt->fetch()) {
            throw new BookingException(
                'Tài khoản không hoạt động.',
                'MEMBER_NOT_ACTIVE',
                403
            );
        }
    }

    private function lockTrainerForAccount(int $memberId): int
    {
        $stmt = $this->db->prepare(
            "SELECT t.trainer_id
             FROM personal_trainer t
             INNER JOIN member m ON m.member_id = t.member_id
             WHERE t.member_id = :member_id
               AND t.is_active = 1
               AND m.role = 'trainer'
               AND m.status = 'active'
             FOR UPDATE"
        );
        $stmt->execute(['member_id' => $memberId]);
        $trainerId = $stmt->fetchColumn();

        if ($trainerId === false) {
            throw new BookingException(
                'Tài khoản chưa được liên kết với huấn luyện viên đang hoạt động.',
                'TRAINER_ACCOUNT_NOT_LINKED',
                403
            );
        }

        return (int) $trainerId;
    }

    private function lockTrainer(int $trainerId): void
    {
        $stmt = $this->db->prepare(
            'SELECT trainer_id
             FROM personal_trainer
             WHERE trainer_id = :trainer_id
               AND is_active = 1
             FOR UPDATE'
        );
        $stmt->execute(['trainer_id' => $trainerId]);
        if (!$stmt->fetch()) {
            throw new BookingException(
                'Huấn luyện viên hiện không hoạt động.',
                'TRAINER_NOT_AVAILABLE',
                409
            );
        }
    }

    private function getTrainerIdForAccount(int $memberId): int
    {
        $stmt = $this->db->prepare(
            "SELECT t.trainer_id
             FROM personal_trainer t
             INNER JOIN member m ON m.member_id = t.member_id
             WHERE t.member_id = :member_id
               AND t.is_active = 1
               AND m.role = 'trainer'
               AND m.status = 'active'
             LIMIT 1"
        );
        $stmt->execute(['member_id' => $memberId]);
        $trainerId = $stmt->fetchColumn();

        if ($trainerId === false) {
            throw new BookingException(
                'Tài khoản chưa được liên kết với huấn luyện viên đang hoạt động.',
                'TRAINER_ACCOUNT_NOT_LINKED',
                403
            );
        }

        return (int) $trainerId;
    }

    private function getTrainerAccountId(int $trainerId): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT member_id
             FROM personal_trainer
             WHERE trainer_id = :trainer_id'
        );
        $stmt->execute(['trainer_id' => $trainerId]);
        $memberId = $stmt->fetchColumn();

        return $memberId === false || $memberId === null
            ? null
            : (int) $memberId;
    }

    private function scheduleHasBookings(int $scheduleId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT booking_id FROM booking WHERE schedule_id = :schedule_id LIMIT 1'
        );
        $stmt->execute(['schedule_id' => $scheduleId]);

        return $stmt->fetchColumn() !== false;
    }

    private function bookingHasStarted(array $booking): bool
    {
        return (int) $booking['seconds_until_start'] <= 0;
    }

    private function assertNoPendingChangeRequest(int $bookingId): void
    {
        $stmt = $this->db->prepare(
            "SELECT request_id
             FROM booking_change_request
             WHERE booking_id = :booking_id
               AND status = 'pending'
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute(['booking_id' => $bookingId]);
        if ($stmt->fetch()) {
            throw new BookingException(
                'Booking đã có yêu cầu đổi lịch đang chờ xử lý.',
                'BOOKING_CHANGE_REQUEST_EXISTS',
                409
            );
        }
    }

    private function cancelPendingChangeRequests(int $bookingId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE booking_change_requests
             SET status = 'Withdrawn'
             WHERE booking_id = :booking_id
               AND status = 'pending'"
        );
        $stmt->execute(['booking_id' => $bookingId]);
    }

    private function bookingSnapshot(array $booking): array
    {
        return [
            'booking_id' => (int) $booking['booking_id'],
            'member_id' => (int) $booking['member_id'],
            'schedule_id' => (int) $booking['schedule_id'],
            'status' => $booking['status'],
            'work_date' => $booking['work_date'],
            'start_time' => $booking['start_time'],
            'end_time' => $booking['end_time']
        ];
    }

    private function recordBookingAudit(
        int $bookingId,
        ?int $actorMemberId,
        string $action,
        ?string $reason,
        ?array $before,
        ?array $after
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs
                (entity_id, actor_id, action, entity_type, old_value, new_value)
             VALUES
                (:booking_id, :actor_member_id, :action, \'Booking\', :before_data, JSON_OBJECT(\'reason\', :reason, \'booking\', JSON_EXTRACT(:after_data, \'$\')))'
        );
        $stmt->execute([
            'booking_id' => $bookingId,
            'actor_member_id' => $actorMemberId,
            'action' => $action,
            'reason' => $reason,
            'before_data' => $before === null
                ? null
                : json_encode($before, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'after_data' => $after === null
                ? null
                : json_encode($after, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        ]);
    }

    private function notifyBookingTransition(
        array $booking,
        string $action,
        string $reason
    ): void {
        $memberId = (int) $booking['member_id'];
        $bookingId = (int) $booking['booking_id'];
        $details = match ($action) {
            'confirm' => [
                'Lịch tập được xác nhận',
                'Booking của bạn đã được xác nhận.'
            ],
            'reject' => [
                'Yêu cầu đặt lịch bị từ chối',
                'Yêu cầu đặt lịch của bạn bị từ chối: ' . trim($reason)
            ],
            'complete' => [
                'Buổi tập đã hoàn thành',
                'Buổi tập với PT đã được đánh dấu hoàn thành.'
            ],
            'cancel' => [
                'Lịch tập đã bị hủy',
                'Booking của bạn đã bị Admin/Staff hủy: ' . trim($reason)
            ],
            default => null
        };

        if ($details === null) {
            throw new LogicException('Trạng thái booking không có thông báo tương ứng.');
        }

        $this->createNotification(
            $memberId,
            $bookingId,
            $details[0],
            $details[1]
        );

        if (isset($booking['trainer_id'])) {
            $trainerAccountId = $this->getTrainerAccountId((int) $booking['trainer_id']);
            if ($trainerAccountId !== null && $trainerAccountId !== $memberId) {
                $this->createNotification(
                    $trainerAccountId,
                    $bookingId,
                    $details[0],
                    $details[1]
                );
            }
        }
    }

    private function insertAccount(
        string $fullName,
        string $email,
        string $phone,
        string $password,
        string $role
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO users
                (full_name, email, phone, password_hash, role)
             VALUES
                (:full_name, :email, :phone, :password_hash, :role)'
        );
        $stmt->execute([
            'full_name' => trim($fullName),
            'email' => strtolower(trim($email)),
            'phone' => trim($phone),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function saveStaffPermissions(int $staffId, bool $canConfirm, bool $canCancel, int $actorId): void
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $lock = $this->db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
            $lock->execute([$staffId]);
            $lock->fetchColumn();
            $remove = $this->db->prepare(
                "DELETE FROM staff_permissions WHERE staff_id = ?
                 AND permission IN ('BOOKING_MANAGE', 'BOOKING_CONFIRM', 'BOOKING_CANCEL')"
            );
            $remove->execute([$staffId]);
            $grant = $this->db->prepare(
                'INSERT INTO staff_permissions (staff_id, permission, granted_by) VALUES (?, ?, ?)'
            );
            foreach (['BOOKING_CONFIRM' => $canConfirm, 'BOOKING_CANCEL' => $canCancel] as $permission => $enabled) {
                if ($enabled) {
                    $grant->execute([$staffId, $permission, $actorId]);
                }
            }
            if ($ownsTransaction) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($ownsTransaction) {
                $this->rollBackIfActive();
            }
            throw $e;
        }
    }

    private function rollBackIfActive(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    private function invalidBookingData(string $message): BookingException
    {
        return new BookingException($message, 'INVALID_BOOKING_DATA', 422);
    }

    private function isValidTime(string $time): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time) === 1;
    }

    public function cancelBooking(
        int $memberId,
        int $bookingId,
        string $reason = ''
    ): void {
        if (
            $memberId <= 0
            || $bookingId <= 0
            || $this->textLength($reason) > 255
        ) {
            throw new BookingException(
                'Thông tin hủy lịch không hợp lệ.',
                'INVALID_BOOKING_DATA',
                422
            );
        }

        $this->db->beginTransaction();

        try {
            $bookingStmt = $this->db->prepare(
                'SELECT
                    b.status,
                    s.work_date,
                    s.start_time,
                    s.trainer_id,
                    t.full_name AS trainer_name,
                    TIMESTAMPDIFF(
                        SECOND,
                        (UTC_TIMESTAMP() + INTERVAL 7 HOUR),
                        TIMESTAMP(s.work_date, s.start_time)
                    ) AS seconds_until_start
                 FROM booking b
                 INNER JOIN trainer_schedule s
                    ON s.schedule_id = b.schedule_id
                 INNER JOIN personal_trainer t
                    ON t.trainer_id = s.trainer_id
                 WHERE b.booking_id = :booking_id
                   AND b.member_id = :member_id
                 FOR UPDATE'
            );
            $bookingStmt->execute([
                'booking_id' => $bookingId,
                'member_id' => $memberId
            ]);
            $booking = $bookingStmt->fetch();

            if (!$booking) {
                throw new BookingException(
                    'Không tìm thấy lịch tập của bạn.',
                    'BOOKING_NOT_FOUND',
                    404
                );
            }

            if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
                throw new BookingException(
                    'Chỉ có thể hủy lịch đang chờ xác nhận hoặc đã được xác nhận.',
                    'BOOKING_STATE_INVALID',
                    409
                );
            }

            $minimumSeconds = self::MEMBER_CANCELLATION_LIMIT_HOURS * 3600;

            if ((int) $booking['seconds_until_start'] < $minimumSeconds) {
                throw new BookingException(
                    'Bạn chỉ có thể tự hủy lịch trước giờ tập ít nhất 24 tiếng.',
                    'BOOKING_CANCELLATION_DEADLINE_PASSED',
                    409
                );
            }

            $cancelReason = $reason !== '' ? $reason : 'Hội viên hủy lịch.';
            $updateStmt = $this->db->prepare(
                "UPDATE bookings
                 SET status = 'cancelled',
                     cancel_reason = :cancel_reason
                 WHERE id = :booking_id"
            );
            $updateStmt->execute([
                'cancel_reason' => $cancelReason,
                'booking_id' => $bookingId
            ]);
            $this->cancelPendingChangeRequests($bookingId);
            $this->recordBookingAudit(
                $bookingId,
                $memberId,
                'member_cancelled',
                $cancelReason,
                ['status' => $booking['status']],
                ['status' => 'cancelled', 'cancel_reason' => $cancelReason]
            );

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }

        $this->createNotification(
            $memberId,
            $bookingId,
            'Bạn đã hủy lịch tập',
            sprintf(
                'Lịch tập với huấn luyện viên %s lúc %s ngày %s đã được hủy.',
                $booking['trainer_name'],
                substr($booking['start_time'], 0, 5),
                date('d/m/Y', strtotime($booking['work_date']))
            )
        );

        $trainerAccountId = $this->getTrainerAccountId((int) $booking['trainer_id']);
        if ($trainerAccountId !== null) {
            $this->createNotification(
                $trainerAccountId,
                $bookingId,
                'Booking đã bị hội viên hủy',
                'Hội viên đã hủy booking này.'
            );
        }
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date
            && DateTimeImmutable::getLastErrors() === false;
    }

    private function textLength(string $value): int
    {
        $count = preg_match_all('/./us', $value);

        return $count === false ? PHP_INT_MAX : $count;
    }

    private function getTrainerName(int $trainerId): string
    {
        $stmt = $this->db->prepare(
            'SELECT full_name FROM personal_trainer WHERE trainer_id = :trainer_id'
        );
        $stmt->execute(['trainer_id' => $trainerId]);
        $trainerName = $stmt->fetchColumn();

        if ($trainerName === false) {
            error_log('Booking notification could not resolve trainer ' . $trainerId);
            return 'huấn luyện viên';
        }

        return (string) $trainerName;
    }

    private function createNotification(
        int $memberId,
        int $bookingId,
        string $title,
        string $message
    ): void {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO notifications
                    (user_id, reference_id, type, title, content)
                 VALUES
                    (:member_id, :booking_id, \'Booking\', :title, :message)'
            );
            $stmt->execute([
                'member_id' => $memberId,
                'booking_id' => $bookingId,
                'title' => $title,
                'message' => $message
            ]);
        } catch (Throwable $e) {
            error_log(
                'Booking notification creation failed for booking '
                . $bookingId . ': ' . $e->getMessage()
            );
        }
    }
}
