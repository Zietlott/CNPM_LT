-- =============================================================================
-- FITZONE - Database dùng chung (TV3: Database, Membership và PT)
-- DBMS      : MySQL 8.0+ / MariaDB 10.4+ (XAMPP mặc định đều chạy được)
-- Charset   : utf8mb4 (lưu được tiếng Việt có dấu)
-- Cách chạy : mysql -u root -p < database/fitzone.sql
--             hoặc phpMyAdmin > Import. File chạy lại nhiều lần được (reset DB).
-- Mật khẩu của TẤT CẢ tài khoản mẫu: Fitzone@123  (đã băm bcrypt, dùng password_verify)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS fitzone
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE fitzone;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW  IF EXISTS v_available_slots;
DROP VIEW  IF EXISTS v_booking_detail;
DROP TABLE IF EXISTS notification;
DROP TABLE IF EXISTS booking;
DROP TABLE IF EXISTS trainer_schedule;
DROP TABLE IF EXISTS personal_trainer;
DROP TABLE IF EXISTS payment;
DROP TABLE IF EXISTS membership;
DROP TABLE IF EXISTS membership_plan;
DROP TABLE IF EXISTS member;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- NHÓM 1: TÀI KHOẢN & MEMBERSHIP
-- =============================================================================

-- Tài khoản người dùng (hội viên + admin). Form đăng ký: full_name, email, phone, password.
CREATE TABLE member (
    member_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    phone         VARCHAR(20)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,                       -- password_hash() của PHP, KHÔNG lưu mật khẩu thô
    role          ENUM('member','admin') NOT NULL DEFAULT 'member',
    status        ENUM('active','locked') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (member_id),
    UNIQUE KEY uq_member_email (email),
    UNIQUE KEY uq_member_phone (phone)
) ENGINE=InnoDB;

-- Danh mục gói hội viên (Basic / Premium / VIP) - dữ liệu cấu hình, ít thay đổi.
CREATE TABLE membership_plan (
    plan_id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name             VARCHAR(50)  NOT NULL,
    description      VARCHAR(255) NOT NULL,
    price            DECIMAL(12,0) NOT NULL,                   -- VNĐ, không có phần thập phân
    duration_months  TINYINT UNSIGNED NOT NULL,                -- thời hạn gói (tháng)
    features         JSON NOT NULL,                            -- mảng quyền lợi, vd: ["Sử dụng khu vực tập gym", ...]
    priority_booking TINYINT(1) NOT NULL DEFAULT 0,            -- 1 = được ưu tiên đặt lịch (VIP)
    is_popular       TINYINT(1) NOT NULL DEFAULT 0,            -- 1 = hiện nhãn "ĐƯỢC QUAN TÂM"
    is_active        TINYINT(1) NOT NULL DEFAULT 1,            -- 0 = ngừng bán, KHÔNG xóa để giữ lịch sử
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (plan_id),
    UNIQUE KEY uq_plan_name (name),
    CONSTRAINT chk_plan_price    CHECK (price > 0),
    CONSTRAINT chk_plan_duration CHECK (duration_months > 0)
) ENGINE=InnoDB;

-- Gói mà từng hội viên đã đăng ký (1 member có nhiều membership theo thời gian).
CREATE TABLE membership (
    membership_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id     INT UNSIGNED NOT NULL,
    plan_id       INT UNSIGNED NOT NULL,
    start_date    DATE NULL,                                   -- NULL khi chưa thanh toán
    end_date      DATE NULL,                                   -- NULL khi chưa thanh toán
    status        ENUM('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (membership_id),
    KEY idx_membership_member_status (member_id, status),
    KEY idx_membership_end_date (end_date),
    CONSTRAINT fk_membership_member FOREIGN KEY (member_id) REFERENCES member (member_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_membership_plan FOREIGN KEY (plan_id) REFERENCES membership_plan (plan_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_membership_dates CHECK (end_date IS NULL OR end_date > start_date),
    CONSTRAINT chk_membership_active_has_dates
        CHECK (status NOT IN ('active','expired') OR (start_date IS NOT NULL AND end_date IS NOT NULL))
) ENGINE=InnoDB;

-- Giao dịch thanh toán cho một membership (1 membership có thể có nhiều lần thanh toán: thất bại rồi thử lại).
CREATE TABLE payment (
    payment_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    membership_id    INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,                    -- người trả tiền (tiện tra cứu lịch sử)
    amount           DECIMAL(12,0) NOT NULL,                   -- số tiền thực trả (chụp lại giá tại thời điểm mua)
    method           ENUM('cash','bank_transfer','e_wallet','card') NOT NULL,
    status           ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    transaction_code VARCHAR(64) NULL,                         -- mã giao dịch ngân hàng / ví điện tử
    paid_at          DATETIME NULL,                            -- chỉ có giá trị khi status = 'paid'
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (payment_id),
    UNIQUE KEY uq_payment_transaction (transaction_code),
    KEY idx_payment_membership (membership_id),
    KEY idx_payment_member (member_id),
    CONSTRAINT fk_payment_membership FOREIGN KEY (membership_id) REFERENCES membership (membership_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payment_member FOREIGN KEY (member_id) REFERENCES member (member_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_payment_amount CHECK (amount > 0)
) ENGINE=InnoDB;

-- =============================================================================
-- NHÓM 2: PERSONAL TRAINER & LỊCH LÀM VIỆC
-- =============================================================================

CREATE TABLE personal_trainer (
    trainer_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name        VARCHAR(100) NOT NULL,
    specialty        VARCHAR(100) NOT NULL,                    -- vd: Strength Training
    experience_years TINYINT UNSIGNED NOT NULL DEFAULT 0,      -- giao diện hiển thị "5 năm kinh nghiệm"
    description      VARCHAR(255) NULL,
    initials         VARCHAR(4) NULL,                          -- chữ viết tắt trên avatar (MA, QB...)
    color_class      VARCHAR(30) NULL,                         -- class CSS màu avatar (trainer-green...)
    is_active        TINYINT(1) NOT NULL DEFAULT 1,            -- 0 = PT nghỉ việc, ẩn khỏi giao diện
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (trainer_id),
    KEY idx_trainer_active (is_active)
) ENGINE=InnoDB;

-- Mỗi dòng = 1 khung giờ làm việc (1 slot) của 1 PT trong 1 ngày.
CREATE TABLE trainer_schedule (
    schedule_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    trainer_id  INT UNSIGNED NOT NULL,
    work_date   DATE NOT NULL,
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    status      ENUM('available','booked','unavailable') NOT NULL DEFAULT 'available',
                -- available   = còn trống (giao diện: "Còn trống")
                -- booked      = đã có người đặt (giao diện: "Đã kín lịch")
                -- unavailable = PT nghỉ / admin khóa slot
    PRIMARY KEY (schedule_id),
    UNIQUE KEY uq_schedule_slot (trainer_id, work_date, start_time),   -- 1 PT không thể có 2 slot trùng giờ bắt đầu
    KEY idx_schedule_date_status (work_date, status),
    CONSTRAINT fk_schedule_trainer FOREIGN KEY (trainer_id) REFERENCES personal_trainer (trainer_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_schedule_time CHECK (end_time > start_time)
) ENGINE=InnoDB;

-- =============================================================================
-- NHÓM 3: BOOKING & NOTIFICATION (phối hợp TV4)
-- =============================================================================

-- Đặt lịch tập: hội viên đặt 1 slot của PT. Trainer/ngày/giờ lấy qua schedule_id (không lưu trùng).
CREATE TABLE booking (
    booking_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id     INT UNSIGNED NOT NULL,
    schedule_id   INT UNSIGNED NOT NULL,
    membership_id INT UNSIGNED NOT NULL,                       -- gói đang dùng để đặt (phải đang 'active')
    note          VARCHAR(500) NULL,                           -- ô "Ghi chú" trên form đặt lịch
    status        ENUM('pending','confirmed','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
    cancel_reason VARCHAR(255) NULL,                           -- lý do hủy / từ chối
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Cột sinh tự động: chỉ có giá trị khi booking còn hiệu lực. UNIQUE trên cột này đảm bảo
    -- 1 slot chỉ có tối đa 1 booking pending/confirmed => chặn đặt trùng ngay cả khi 2 người bấm cùng lúc.
    active_schedule_id INT UNSIGNED
        GENERATED ALWAYS AS (IF(status IN ('pending','confirmed'), schedule_id, NULL)) STORED,
    PRIMARY KEY (booking_id),
    UNIQUE KEY uq_booking_active_slot (active_schedule_id),
    KEY idx_booking_member (member_id, status),
    KEY idx_booking_schedule (schedule_id),
    KEY idx_booking_membership (membership_id),
    CONSTRAINT fk_booking_member FOREIGN KEY (member_id) REFERENCES member (member_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_booking_schedule FOREIGN KEY (schedule_id) REFERENCES trainer_schedule (schedule_id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_booking_membership FOREIGN KEY (membership_id) REFERENCES membership (membership_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Thông báo gửi đến hội viên (đặt lịch, xác nhận, hủy, gói sắp hết hạn, thanh toán...).
CREATE TABLE notification (
    notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id       INT UNSIGNED NOT NULL,                     -- người nhận
    booking_id      INT UNSIGNED NULL,                         -- có thể NULL nếu thông báo không liên quan booking
    type            ENUM('booking_created','booking_confirmed','booking_rejected','booking_cancelled',
                         'payment_success','membership_activated','membership_expiring','membership_expired',
                         'system') NOT NULL,
    title           VARCHAR(150) NOT NULL,
    message         VARCHAR(500) NOT NULL,
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id),
    KEY idx_notification_member_read (member_id, is_read, created_at),
    CONSTRAINT fk_notification_member FOREIGN KEY (member_id) REFERENCES member (member_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notification_booking FOREIGN KEY (booking_id) REFERENCES booking (booking_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================================
-- VIEW TIỆN DỤNG CHO TV2 / TV4
-- =============================================================================

-- Trang "Lịch rảnh PT" / form đặt lịch: slot còn trống từ hôm nay trở đi.
CREATE VIEW v_available_slots AS
SELECT s.schedule_id, s.work_date, s.start_time, s.end_time, s.status,
       t.trainer_id, t.full_name AS trainer_name, t.specialty
FROM trainer_schedule s
JOIN personal_trainer t ON t.trainer_id = s.trainer_id
WHERE t.is_active = 1
  AND s.status = 'available'
  AND s.work_date >= CURRENT_DATE();

-- Danh sách booking đầy đủ thông tin (hội viên + PT + thời gian).
CREATE VIEW v_booking_detail AS
SELECT b.booking_id, b.status, b.note, b.cancel_reason, b.created_at,
       m.member_id, m.full_name AS member_name, m.email AS member_email,
       t.trainer_id, t.full_name AS trainer_name, t.specialty,
       s.schedule_id, s.work_date, s.start_time, s.end_time,
       b.membership_id
FROM booking b
JOIN member m           ON m.member_id   = b.member_id
JOIN trainer_schedule s ON s.schedule_id = b.schedule_id
JOIN personal_trainer t ON t.trainer_id  = s.trainer_id;

-- =============================================================================
-- DỮ LIỆU MẪU
-- (ngày mẫu bám theo giao diện schedule.php: 21/10/2026 - 25/10/2026)
-- =============================================================================

-- Mật khẩu chung cho tài khoản mẫu: Fitzone@123
SET @pw = '$2y$12$qUVDOWtz7OGxTZXDtQnVourk6KW7FuCE/HXmKOUEO7eklCCbLghue';

INSERT INTO member (member_id, full_name, email, phone, password_hash, role, status) VALUES
(1, 'Quản trị viên',   'admin@fitzone.vn',  '0900000001', @pw, 'admin',  'active'),
(2, 'Nguyễn Văn An',   'an@example.com',    '0901234567', @pw, 'member', 'active'),
(3, 'Trần Thị Bình',   'binh@example.com',  '0902345678', @pw, 'member', 'active'),
(4, 'Lê Thị Chi',      'chi@example.com',   '0903456789', @pw, 'member', 'active'),
(5, 'Phạm Quốc Dũng',  'dung@example.com',  '0904567890', @pw, 'member', 'active');

INSERT INTO membership_plan (plan_id, name, description, price, duration_months, features, priority_booking, is_popular) VALUES
(1, 'Basic',   'Phù hợp cho người mới bắt đầu tập luyện.',                 399000,  1,
   JSON_ARRAY('Sử dụng khu vực tập gym','Hướng dẫn sử dụng thiết bị','Theo dõi tiến độ tập luyện'), 0, 0),
(2, 'Premium', 'Lựa chọn cân bằng cho việc tập luyện thường xuyên.',       999000,  3,
   JSON_ARRAY('Toàn bộ quyền lợi Basic','Tham gia lớp tập nhóm','Đánh giá thể lực định kỳ'),        0, 1),
(3, 'VIP',     'Trải nghiệm tập luyện với nhiều quyền lợi hơn.',          2499000,  6,
   JSON_ARRAY('Toàn bộ quyền lợi Premium','Ưu tiên đặt lịch tập','Tư vấn kế hoạch tập luyện'),      1, 0);

INSERT INTO membership (membership_id, member_id, plan_id, start_date, end_date, status) VALUES
(1, 2, 3, '2026-09-15', '2027-03-15', 'active'),     -- An: VIP đang dùng
(2, 3, 2, '2026-10-01', '2027-01-01', 'active'),     -- Bình: Premium đang dùng
(3, 4, 1, '2026-09-01', '2026-10-01', 'expired'),    -- Chi: Basic đã hết hạn
(4, 5, 2, NULL,         NULL,         'pending');    -- Dũng: chọn Premium, chưa thanh toán

INSERT INTO payment (payment_id, membership_id, member_id, amount, method, status, transaction_code, paid_at) VALUES
(1, 1, 2, 2499000, 'bank_transfer', 'paid',    'BANK20260915001', '2026-09-15 09:30:00'),
(2, 2, 3,  999000, 'e_wallet',      'paid',    'MOMO20261001002', '2026-10-01 18:05:00'),
(3, 3, 4,  399000, 'cash',          'paid',    NULL,              '2026-09-01 10:00:00'),
(4, 4, 5,  999000, 'bank_transfer', 'pending', NULL,              NULL);

INSERT INTO personal_trainer (trainer_id, full_name, specialty, experience_years, description, initials, color_class) VALUES
(1, 'Nguyễn Minh Anh', 'Strength Training',   5, 'Hỗ trợ xây dựng sức mạnh và cải thiện kỹ thuật tập luyện.',            'MA', 'trainer-green'),
(2, 'Trần Quốc Bảo',   'Fitness & Cardio',    4, 'Hướng dẫn các bài tập thể lực và xây dựng thói quen vận động.',        'QB', 'trainer-blue'),
(3, 'Lê Hoàng Phúc',   'Functional Training', 6, 'Hỗ trợ cải thiện khả năng vận động và kỹ thuật bài tập.',              'HP', 'trainer-orange'),
(4, 'Phạm Thu Hà',     'Yoga & Mobility',     3, 'Hướng dẫn các bài tập linh hoạt và cải thiện khả năng vận động.',      'TH', 'trainer-purple');

INSERT INTO trainer_schedule (schedule_id, trainer_id, work_date, start_time, end_time, status) VALUES
( 1, 1, '2026-10-21', '08:00:00', '09:00:00', 'available'),
( 2, 2, '2026-10-21', '09:00:00', '10:00:00', 'booked'),
( 3, 2, '2026-10-22', '15:00:00', '16:00:00', 'available'),
( 4, 3, '2026-10-23', '09:00:00', '10:00:00', 'available'),
( 5, 4, '2026-10-24', '14:00:00', '15:00:00', 'booked'),
( 6, 1, '2026-10-25', '08:00:00', '09:00:00', 'available'),
( 7, 1, '2026-10-21', '07:00:00', '08:00:00', 'available'),
( 8, 1, '2026-10-22', '16:00:00', '17:00:00', 'available'),
( 9, 2, '2026-10-23', '17:00:00', '18:00:00', 'available'),
(10, 3, '2026-10-24', '15:00:00', '16:00:00', 'available'),
(11, 4, '2026-10-22', '07:00:00', '08:00:00', 'available'),
(12, 4, '2026-10-25', '16:00:00', '17:00:00', 'available'),   -- từng bị hủy => đã trả lại slot
(13, 3, '2026-10-25', '09:00:00', '10:00:00', 'unavailable'), -- PT nghỉ
(14, 1, '2026-10-19', '08:00:00', '09:00:00', 'booked');      -- slot quá khứ, buổi đã hoàn thành

-- Đủ 4 trạng thái chính: confirmed, pending, cancelled, completed
INSERT INTO booking (booking_id, member_id, schedule_id, membership_id, note, status, cancel_reason) VALUES
(1, 2,  2, 1, 'Muốn tập tăng sức bền.',               'confirmed', NULL),
(2, 3,  5, 2, 'Mới tập yoga lần đầu.',                'pending',   NULL),
(3, 3, 12, 2, NULL,                                    'cancelled', 'Bận việc đột xuất.'),
(4, 2, 14, 1, 'Tập tay và vai.',                       'completed', NULL);

INSERT INTO notification (notification_id, member_id, booking_id, type, title, message, is_read) VALUES
(1, 2, NULL, 'membership_activated', 'Gói VIP đã kích hoạt',     'Gói VIP của bạn có hiệu lực đến 15/03/2027.', 1),
(2, 2, 1,    'booking_confirmed',    'Lịch tập được xác nhận',    'Buổi tập với PT Trần Quốc Bảo lúc 09:00 ngày 21/10/2026 đã được xác nhận.', 0),
(3, 3, 2,    'booking_created',      'Đã gửi yêu cầu đặt lịch',   'Yêu cầu đặt lịch với PT Phạm Thu Hà lúc 14:00 ngày 24/10/2026 đang chờ xác nhận.', 0),
(4, 3, 3,    'booking_cancelled',    'Bạn đã hủy lịch tập',       'Buổi tập với PT Phạm Thu Hà lúc 16:00 ngày 25/10/2026 đã được hủy.', 1),
(5, 4, NULL, 'membership_expired',   'Gói Basic đã hết hạn',      'Gói Basic của bạn đã hết hạn ngày 01/10/2026. Hãy gia hạn để tiếp tục đặt lịch.', 0);