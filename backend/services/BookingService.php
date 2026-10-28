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
                  AND TIMESTAMP(s.work_date, s.start_time) > CURRENT_TIMESTAMP
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
                  AND TIMESTAMP(s.work_date, s.start_time) > CURRENT_TIMESTAMP
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
                    t.full_name AS trainer_name,
                    t.specialty,
                    s.work_date,
                    s.start_time,
                    s.end_time,
                    CASE
                        WHEN b.status IN ('pending', 'confirmed')
                         AND TIMESTAMPDIFF(
                             SECOND,
                             CURRENT_TIMESTAMP,
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
                   AND TIMESTAMP(work_date, start_time) > CURRENT_TIMESTAMP
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
                "INSERT INTO booking
                    (member_id, schedule_id, membership_id, note, status)
                 VALUES
                    (:member_id, :schedule_id, :membership_id, :note, 'pending')"
            );
            $insertStmt->execute([
                'member_id' => $memberId,
                'schedule_id' => $scheduleId,
                'membership_id' => $membershipId,
                'note' => $note !== '' ? $note : null
            ]);

            $bookingId = (int) $this->db->lastInsertId();
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
            'booking_created',
            'Đã gửi yêu cầu đặt lịch',
            sprintf(
                'Yêu cầu tập với huấn luyện viên %s lúc %s ngày %s đang chờ xác nhận.',
                $this->getTrainerName((int) $trainerId),
                substr($schedule['start_time'], 0, 5),
                date('d/m/Y', strtotime($schedule['work_date']))
            )
        );

        return $bookingId;
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
                    t.full_name AS trainer_name,
                    TIMESTAMPDIFF(
                        SECOND,
                        CURRENT_TIMESTAMP,
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
                "UPDATE booking
                 SET status = 'cancelled',
                     cancel_reason = :cancel_reason
                 WHERE booking_id = :booking_id"
            );
            $updateStmt->execute([
                'cancel_reason' => $cancelReason,
                'booking_id' => $bookingId
            ]);

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
            'booking_cancelled',
            'Bạn đã hủy lịch tập',
            sprintf(
                'Lịch tập với huấn luyện viên %s lúc %s ngày %s đã được hủy.',
                $booking['trainer_name'],
                substr($booking['start_time'], 0, 5),
                date('d/m/Y', strtotime($booking['work_date']))
            )
        );
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
        string $type,
        string $title,
        string $message
    ): void {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO notification
                    (member_id, booking_id, type, title, message)
                 VALUES
                    (:member_id, :booking_id, :type, :title, :message)'
            );
            $stmt->execute([
                'member_id' => $memberId,
                'booking_id' => $bookingId,
                'type' => $type,
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
