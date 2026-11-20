-- =====================================================================
-- Fitness Center Membership & PT Booking App - schema.sql
-- Engine: MySQL 8.0+ / MariaDB 10.5+ (InnoDB, utf8mb4)
-- Quy ước: bảng snake_case số nhiều; tiền dùng DECIMAL(12,2);
--          MỌI cột thời gian lưu theo UTC (backend chuyển đổi khi hiển thị).
-- =====================================================================

DROP DATABASE IF EXISTS fitness_center;
CREATE DATABASE fitness_center CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitness_center;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
-- Module A - Authentication & Account
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(255) NOT NULL,
  username      VARCHAR(50)  NULL,
  password_hash VARCHAR(255) NOT NULL,                -- bcrypt / argon2id
  full_name     VARCHAR(150) NOT NULL,
  phone         VARCHAR(20)  NULL,
  role          ENUM('Member','PT','Staff','Admin') NOT NULL,
  status        ENUM('Active','Locked','Disabled') NOT NULL DEFAULT 'Active',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB;

CREATE TABLE member_profiles (
  user_id           BIGINT UNSIGNED NOT NULL,
  date_of_birth     DATE NULL,
  gender            ENUM('Male','Female','Other') NULL,
  address           VARCHAR(255) NULL,
  emergency_contact VARCHAR(150) NULL,
  health_note       VARCHAR(500) NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_member_profiles_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE pt_profiles (
  user_id            BIGINT UNSIGNED NOT NULL,
  specialty          VARCHAR(255) NOT NULL,
  bio                TEXT NULL,
  experience_years   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  hourly_rate        DECIMAL(12,2) NOT NULL,
  approval_status    ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending', -- chỉ Admin sửa
  accepting_bookings TINYINT(1) NOT NULL DEFAULT 1,                                     -- chỉ Admin/Staff sửa
  PRIMARY KEY (user_id),
  CONSTRAINT fk_pt_profiles_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT ck_pt_profiles_rate CHECK (hourly_rate > 0)
) ENGINE=InnoDB;

CREATE TABLE staff_permissions (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  staff_id   BIGINT UNSIGNED NOT NULL,
  permission VARCHAR(50) NOT NULL,   -- vd: REPORT_VIEW, MEMBERSHIP_MANAGE, PT_MANAGE, BOOKING_MANAGE, PAYMENT_VIEW
  granted_by BIGINT UNSIGNED NOT NULL,
  granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_staff_permission (staff_id, permission),
  CONSTRAINT fk_staff_perm_staff   FOREIGN KEY (staff_id)   REFERENCES users(id),
  CONSTRAINT fk_staff_perm_granter FOREIGN KEY (granted_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE auth_sessions (          -- phục vụ đăng xuất / vô hiệu hóa token
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,       -- SHA-256 của token/session id
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_auth_sessions_token (token_hash),
  KEY idx_auth_sessions_user (user_id),
  CONSTRAINT fk_auth_sessions_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE password_reset_tokens (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,       -- chỉ lưu hash, không lưu token gốc
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,           -- NOT NULL = đã dùng, không được dùng lại
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reset_token_hash (token_hash),
  KEY idx_reset_tokens_user (user_id),
  CONSTRAINT fk_reset_tokens_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Module B - Membership Plan & Membership
-- ---------------------------------------------------------------------
CREATE TABLE membership_plans (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  price         DECIMAL(12,2) NOT NULL,
  duration_days INT UNSIGNED NOT NULL,
  description   VARCHAR(500) NULL,
  status        ENUM('Active','Discontinued') NOT NULL DEFAULT 'Active',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_membership_plans_name (name),
  CONSTRAINT ck_plans_price    CHECK (price >= 0),
  CONSTRAINT ck_plans_duration CHECK (duration_days > 0)
) ENGINE=InnoDB;

CREATE TABLE memberships (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id              BIGINT UNSIGNED NOT NULL,
  plan_id                BIGINT UNSIGNED NOT NULL,
  -- snapshot: sửa/ngừng gói không làm đổi lịch sử Membership đã mua (MEM-02)
  plan_name_snapshot     VARCHAR(100) NOT NULL,
  price_snapshot         DECIMAL(12,2) NOT NULL,
  duration_days_snapshot INT UNSIGNED NOT NULL,
  start_date             DATE NULL,                 -- NULL khi chưa thanh toán
  end_date               DATE NULL,
  status                 ENUM('PendingPayment','Active','Expired','Cancelled') NOT NULL DEFAULT 'PendingPayment',
  renewed_from_id        BIGINT UNSIGNED NULL,      -- gia hạn từ Membership nào
  created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_memberships_member_status (member_id, status),
  KEY idx_memberships_end_date (end_date),
  -- tối đa 1 Membership Active cho mỗi Member
  active_member_id BIGINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'Active', member_id, NULL)) STORED,
  UNIQUE KEY uq_memberships_one_active (active_member_id),
  CONSTRAINT fk_memberships_member  FOREIGN KEY (member_id)       REFERENCES users(id),
  CONSTRAINT fk_memberships_plan    FOREIGN KEY (plan_id)         REFERENCES membership_plans(id),
  CONSTRAINT fk_memberships_renewed FOREIGN KEY (renewed_from_id) REFERENCES memberships(id),
  CONSTRAINT ck_memberships_dates   CHECK (end_date IS NULL OR start_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB;

CREATE TABLE membership_status_history (   -- MEM-07: lưu lịch sử thay đổi
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  membership_id BIGINT UNSIGNED NOT NULL,
  old_status    VARCHAR(20) NULL,
  new_status    VARCHAR(20) NOT NULL,
  reason        VARCHAR(255) NULL,
  changed_by    BIGINT UNSIGNED NULL,       -- NULL = hệ thống
  changed_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ms_history_membership (membership_id),
  CONSTRAINT fk_ms_history_membership FOREIGN KEY (membership_id) REFERENCES memberships(id),
  CONSTRAINT fk_ms_history_user       FOREIGN KEY (changed_by)    REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Module C - Personal Trainer & Schedule
-- Schedule = khung giờ làm việc cố định của PT; 1 Booking chiếm 1 Schedule.
-- ---------------------------------------------------------------------
CREATE TABLE schedules (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pt_id      BIGINT UNSIGNED NOT NULL,
  start_at   DATETIME NOT NULL,
  end_at     DATETIME NOT NULL,
  status     ENUM('Open','Closed') NOT NULL DEFAULT 'Open',   -- Closed = PT tạm đóng khung giờ
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_schedules_pt_start (pt_id, start_at),          -- chặn trùng khung giờ cùng PT
  KEY idx_schedules_pt_time (pt_id, start_at, end_at),
  CONSTRAINT fk_schedules_pt   FOREIGN KEY (pt_id) REFERENCES users(id),
  CONSTRAINT ck_schedules_time CHECK (start_at < end_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Module D - Booking PT
-- State machine: AwaitingPayment -> Pending -> Confirmed -> Completed
--   AwaitingPayment -> Expired | Cancelled ; Pending -> Rejected | Cancelled ; Confirmed -> Cancelled
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id        BIGINT UNSIGNED NOT NULL,
  pt_id            BIGINT UNSIGNED NOT NULL,
  schedule_id      BIGINT UNSIGNED NOT NULL,
  membership_id    BIGINT UNSIGNED NOT NULL,        -- Membership Active tại thời điểm đặt
  start_at         DATETIME NOT NULL,
  end_at           DATETIME NOT NULL,
  duration_minutes INT UNSIGNED NOT NULL,
  fee_snapshot     DECIMAL(12,2) NOT NULL,          -- backend tự tính, không nhận từ frontend
  status           ENUM('AwaitingPayment','Pending','Confirmed','Completed',
                        'Expired','Cancelled','Rejected') NOT NULL DEFAULT 'AwaitingPayment',
  hold_expires_at  DATETIME NULL,                   -- now + 15 phút (so với giờ DB)
  cancelled_by     BIGINT UNSIGNED NULL,
  cancel_reason    VARCHAR(255) NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_bookings_member_status (member_id, status),
  KEY idx_bookings_pt_status (pt_id, status),
  KEY idx_bookings_hold_expiry (status, hold_expires_at),     -- phục vụ job BOOK-12
  KEY idx_bookings_start (start_at),
  -- Không có 2 Booking "đang chiếm" cùng 1 Schedule (BOOK-11, tiêu chí nghiệm thu module C)
  active_schedule_id BIGINT UNSIGNED GENERATED ALWAYS AS (
    IF(status IN ('AwaitingPayment','Pending','Confirmed','Completed'), schedule_id, NULL)) STORED,
  UNIQUE KEY uq_bookings_active_schedule (active_schedule_id),
  CONSTRAINT fk_bookings_member     FOREIGN KEY (member_id)     REFERENCES users(id),
  CONSTRAINT fk_bookings_pt         FOREIGN KEY (pt_id)         REFERENCES users(id),
  CONSTRAINT fk_bookings_schedule   FOREIGN KEY (schedule_id)   REFERENCES schedules(id),
  CONSTRAINT fk_bookings_membership FOREIGN KEY (membership_id) REFERENCES memberships(id),
  CONSTRAINT fk_bookings_cancelled  FOREIGN KEY (cancelled_by)  REFERENCES users(id),
  CONSTRAINT ck_bookings_time CHECK (start_at < end_at),
  CONSTRAINT ck_bookings_fee  CHECK (fee_snapshot >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Module E - Payment & Refund
-- Một Payment thuộc đúng MỘT Membership HOẶC MỘT Booking.
-- ---------------------------------------------------------------------
CREATE TABLE payments (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id      BIGINT UNSIGNED NOT NULL,
  booking_id     BIGINT UNSIGNED NULL,
  membership_id  BIGINT UNSIGNED NULL,
  amount         DECIMAL(12,2) NOT NULL,
  status         ENUM('Pending','Success','Failed') NOT NULL DEFAULT 'Pending',
  transaction_code VARCHAR(64) NULL,                -- mã giao dịch mô phỏng
  paid_at        DATETIME NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payments_transaction_code (transaction_code),
  KEY idx_payments_booking (booking_id, status),
  KEY idx_payments_membership (membership_id, status),
  KEY idx_payments_member (member_id),
  -- Không có 2 Payment Pending đồng thời cho cùng Booking / cùng Membership (PAY-04)
  pending_booking_id    BIGINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'Pending', booking_id, NULL)) STORED,
  pending_membership_id BIGINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'Pending', membership_id, NULL)) STORED,
  UNIQUE KEY uq_payments_one_pending_booking (pending_booking_id),
  UNIQUE KEY uq_payments_one_pending_membership (pending_membership_id),
  success_booking_id    BIGINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'Success', booking_id, NULL)) STORED,
  success_membership_id BIGINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'Success', membership_id, NULL)) STORED,
  UNIQUE KEY uq_payments_one_success_booking (success_booking_id),
  UNIQUE KEY uq_payments_one_success_membership (success_membership_id),
  CONSTRAINT fk_payments_member     FOREIGN KEY (member_id)     REFERENCES users(id),
  CONSTRAINT fk_payments_booking    FOREIGN KEY (booking_id)    REFERENCES bookings(id),
  CONSTRAINT fk_payments_membership FOREIGN KEY (membership_id) REFERENCES memberships(id),
  CONSTRAINT ck_payments_amount CHECK (amount >= 0),
  CONSTRAINT ck_payments_one_target CHECK (
    (booking_id IS NOT NULL AND membership_id IS NULL) OR
    (booking_id IS NULL AND membership_id IS NOT NULL))
) ENGINE=InnoDB;

CREATE TABLE refunds (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  payment_id BIGINT UNSIGNED NOT NULL,
  booking_id BIGINT UNSIGNED NOT NULL,
  amount     DECIMAL(12,2) NOT NULL,
  reason     VARCHAR(255) NOT NULL,
  status     ENUM('Pending','Success','Failed') NOT NULL DEFAULT 'Pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_refunds_payment (payment_id),       -- chống hoàn tiền trùng (PAY-06)
  KEY idx_refunds_booking (booking_id),
  CONSTRAINT fk_refunds_payment FOREIGN KEY (payment_id) REFERENCES payments(id),
  CONSTRAINT fk_refunds_booking FOREIGN KEY (booking_id) REFERENCES bookings(id),
  CONSTRAINT ck_refunds_amount  CHECK (amount > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Module F - Booking Change Request
-- ---------------------------------------------------------------------
CREATE TABLE booking_change_requests (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id      BIGINT UNSIGNED NOT NULL,
  requested_by    BIGINT UNSIGNED NOT NULL,
  old_schedule_id BIGINT UNSIGNED NOT NULL,
  new_schedule_id BIGINT UNSIGNED NOT NULL,         -- cùng PT, slot mới (kiểm tra lại khi duyệt)
  new_start_at    DATETIME NOT NULL,
  new_end_at      DATETIME NOT NULL,
  status          ENUM('Pending','Approved','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
  reject_reason   VARCHAR(255) NULL,
  reviewed_by     BIGINT UNSIGNED NULL,
  reviewed_at     DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_change_req_booking (booking_id, status),
  KEY idx_change_req_new_schedule (new_schedule_id),
  -- Mỗi Booking chỉ có 1 yêu cầu đổi lịch đang chờ
  pending_booking_id BIGINT UNSIGNED GENERATED ALWAYS AS (IF(status = 'Pending', booking_id, NULL)) STORED,
  UNIQUE KEY uq_change_req_one_pending (pending_booking_id),
  CONSTRAINT fk_change_req_booking  FOREIGN KEY (booking_id)      REFERENCES bookings(id),
  CONSTRAINT fk_change_req_user     FOREIGN KEY (requested_by)    REFERENCES users(id),
  CONSTRAINT fk_change_req_old      FOREIGN KEY (old_schedule_id) REFERENCES schedules(id),
  CONSTRAINT fk_change_req_new      FOREIGN KEY (new_schedule_id) REFERENCES schedules(id),
  CONSTRAINT fk_change_req_reviewer FOREIGN KEY (reviewed_by)     REFERENCES users(id),
  CONSTRAINT ck_change_req_time CHECK (new_start_at < new_end_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Module G - PT Earnings
-- ---------------------------------------------------------------------
CREATE TABLE pt_earnings (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pt_id       BIGINT UNSIGNED NOT NULL,
  booking_id  BIGINT UNSIGNED NOT NULL,
  amount      DECIMAL(12,2) NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pt_earnings_booking (booking_id),   -- ghi nhận đúng 1 lần (EARN-02)
  KEY idx_pt_earnings_pt_time (pt_id, recorded_at),
  CONSTRAINT fk_pt_earnings_pt      FOREIGN KEY (pt_id)      REFERENCES users(id),
  CONSTRAINT fk_pt_earnings_booking FOREIGN KEY (booking_id) REFERENCES bookings(id),
  CONSTRAINT ck_pt_earnings_amount  CHECK (amount >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Module H - Notification & Audit Log
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      BIGINT UNSIGNED NOT NULL,
  type         ENUM('Booking','Payment','Refund','Membership','System') NOT NULL,
  title        VARCHAR(150) NOT NULL,
  content      VARCHAR(500) NOT NULL,
  reference_id BIGINT UNSIGNED NULL,                -- id Booking/Payment/... liên quan
  is_read      TINYINT(1) NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user_read (user_id, is_read, created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_id    BIGINT UNSIGNED NULL,                 -- NULL = hệ thống/job
  action      VARCHAR(60) NOT NULL,                 -- vd: USER_LOCK, STAFF_PERMISSION_GRANT
  entity_type VARCHAR(40) NOT NULL,
  entity_id   BIGINT UNSIGNED NULL,
  old_value   JSON NULL,
  new_value   JSON NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_actor (actor_id, created_at),
  KEY idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB;
