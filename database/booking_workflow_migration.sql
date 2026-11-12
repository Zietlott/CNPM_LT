-- Booking workflow extension for an existing FitZone database.
-- Run once after database/fitzone.sql. Existing member accounts and bookings
-- remain intact; PT accounts can be linked by Admin after this migration.

ALTER TABLE member
    MODIFY role ENUM('member','trainer','staff','admin') NOT NULL DEFAULT 'member';

ALTER TABLE personal_trainer
    ADD COLUMN member_id INT UNSIGNED NULL AFTER trainer_id,
    ADD UNIQUE KEY uq_trainer_member (member_id),
    ADD CONSTRAINT fk_trainer_member FOREIGN KEY (member_id) REFERENCES member (member_id)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE notification
    MODIFY type ENUM(
        'booking_created',
        'booking_confirmed',
        'booking_rejected',
        'booking_cancelled',
        'booking_completed',
        'booking_change_requested',
        'booking_change_approved',
        'booking_change_rejected',
        'payment_success',
        'membership_activated',
        'membership_expiring',
        'membership_expired',
        'system'
    ) NOT NULL;

CREATE TABLE booking_change_request (
    request_id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id             INT UNSIGNED NOT NULL,
    requested_by_member_id INT UNSIGNED NOT NULL,
    requested_schedule_id  INT UNSIGNED NOT NULL,
    reason                 VARCHAR(255) NOT NULL,
    status                 ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    reviewed_by_member_id  INT UNSIGNED NULL,
    reviewed_at            DATETIME NULL,
    decision_reason        VARCHAR(255) NULL,
    active_booking_id      INT UNSIGNED
        GENERATED ALWAYS AS (IF(status = 'pending', booking_id, NULL)) STORED,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (request_id),
    UNIQUE KEY uq_booking_change_active (active_booking_id),
    KEY idx_booking_change_booking_status (booking_id, status),
    KEY idx_booking_change_schedule_status (requested_schedule_id, status),
    CONSTRAINT fk_booking_change_booking FOREIGN KEY (booking_id) REFERENCES booking (booking_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_booking_change_requester FOREIGN KEY (requested_by_member_id) REFERENCES member (member_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_booking_change_schedule FOREIGN KEY (requested_schedule_id) REFERENCES trainer_schedule (schedule_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_booking_change_reviewer FOREIGN KEY (reviewed_by_member_id) REFERENCES member (member_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE booking_audit_log (
    audit_id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id       INT UNSIGNED NOT NULL,
    actor_member_id  INT UNSIGNED NULL,
    action           VARCHAR(50) NOT NULL,
    reason           VARCHAR(255) NULL,
    before_data      JSON NULL,
    after_data       JSON NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_id),
    KEY idx_booking_audit_booking (booking_id, created_at),
    KEY idx_booking_audit_actor (actor_member_id, created_at),
    CONSTRAINT fk_booking_audit_booking FOREIGN KEY (booking_id) REFERENCES booking (booking_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_booking_audit_actor FOREIGN KEY (actor_member_id) REFERENCES member (member_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE booking_staff_permission (
    member_id   INT UNSIGNED NOT NULL,
    can_confirm TINYINT(1) NOT NULL DEFAULT 0,
    can_cancel  TINYINT(1) NOT NULL DEFAULT 0,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (member_id),
    CONSTRAINT fk_booking_staff_permission_member FOREIGN KEY (member_id) REFERENCES member (member_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO trainer_schedule
    (trainer_id, work_date, start_time, end_time, status)
SELECT candidate.trainer_id,
       candidate.work_date,
       candidate.start_time,
       candidate.end_time,
       'available'
FROM (
    SELECT 1 AS trainer_id,
           DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY) AS work_date,
           '07:00:00' AS start_time,
           '08:00:00' AS end_time
    UNION ALL
    SELECT 2, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), '09:00:00', '10:00:00'
    UNION ALL
    SELECT 3, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), '15:00:00', '16:00:00'
    UNION ALL
    SELECT 4, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), '16:00:00', '17:00:00'
) AS candidate
WHERE NOT EXISTS (
    SELECT 1
    FROM trainer_schedule existing
    WHERE existing.trainer_id = candidate.trainer_id
      AND existing.work_date = candidate.work_date
      AND existing.start_time = candidate.start_time
);
