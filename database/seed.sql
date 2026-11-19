-- =====================================================================
-- Fitness Center - seed.sql (dữ liệu mẫu để dev/test)
-- Chạy SAU schema.sql.
-- Mật khẩu của TẤT CẢ tài khoản mẫu: Fitness@123  (bcrypt cost 10)
-- CHỈ DÙNG CHO MÔI TRƯỜNG DEV - đổi/xóa trước khi triển khai thật.
-- =====================================================================
USE fitness_center;
SET time_zone = '+00:00';
SET @pw = '$2b$10$L1gcpID6Ulvgu/YCF6NrcebAjj9k0xVcZC8l1k68NbSnZXdaZwjFW';

-- ---------- Users: 1 Admin, 2 Staff, 3 PT, 4 Member (1 Member bị khóa để test AUTH-02) ----------
INSERT INTO users (id, email, username, password_hash, full_name, phone, role, status) VALUES
 (1,  'admin@fitness.local',   'admin',    @pw, 'Quản Trị Viên',      '0900000001', 'Admin',  'Active'),
 (2,  'staff1@fitness.local',  'staff1',   @pw, 'Lê Thị Hoa',         '0900000002', 'Staff',  'Active'),
 (3,  'staff2@fitness.local',  'staff2',   @pw, 'Trần Văn Nam',       '0900000003', 'Staff',  'Active'),
 (4,  'pt1@fitness.local',     'pt_minh',  @pw, 'Đỗ Quang Minh',      '0900000011', 'PT',     'Active'),
 (5,  'pt2@fitness.local',     'pt_lan',   @pw, 'Vũ Ngọc Lan',        '0900000012', 'PT',     'Active'),
 (6,  'pt3@fitness.local',     'pt_khoa',  @pw, 'Bùi Anh Khoa',       '0900000013', 'PT',     'Active'),
 (7,  'member1@fitness.local', 'member1',  @pw, 'Nguyễn Văn An',      '0900000021', 'Member', 'Active'),
 (8,  'member2@fitness.local', 'member2',  @pw, 'Phạm Thị Bình',      '0900000022', 'Member', 'Active'),
 (9,  'member3@fitness.local', 'member3',  @pw, 'Hoàng Minh Châu',    '0900000023', 'Member', 'Active'),
 (10, 'locked@fitness.local',  'locked',   @pw, 'Tài Khoản Bị Khóa',  '0900000024', 'Member', 'Locked');

INSERT INTO member_profiles (user_id, date_of_birth, gender, address, emergency_contact, health_note) VALUES
 (7,  '1998-03-14', 'Male',   'Quận 1, TP.HCM',     'Nguyễn Văn Bố - 0911111111', NULL),
 (8,  '2000-07-02', 'Female', 'Quận 3, TP.HCM',     'Phạm Văn Cha - 0922222222',  'Đau gối nhẹ'),
 (9,  '1995-11-25', 'Female', 'TP. Thủ Đức',        'Hoàng Văn Anh - 0933333333', NULL),
 (10, '1999-01-01', 'Male',   'Quận 7, TP.HCM',     NULL,                         NULL);

INSERT INTO pt_profiles (user_id, specialty, bio, experience_years, hourly_rate, approval_status, accepting_bookings) VALUES
 (4, 'Tăng cơ, Powerlifting',      'PT 6 năm kinh nghiệm, chuyên tăng cơ và sức mạnh.',  6, 300000.00, 'Approved', 1),
 (5, 'Giảm cân, Yoga, Cardio',     'Chuyên giảm mỡ an toàn và cải thiện thể lực tổng quát.', 4, 250000.00, 'Approved', 1),
 (6, 'Phục hồi chấn thương',       'Cựu vận động viên, chuyên phục hồi chức năng.',      8, 350000.00, 'Approved', 1);

-- Staff 2 có quyền xem báo cáo (REPORT_VIEW); Staff 3 chỉ quản lý Membership/PT
INSERT INTO staff_permissions (staff_id, permission, granted_by) VALUES
 (2, 'REPORT_VIEW',        1),
 (2, 'BOOKING_MANAGE',     1),
 (2, 'PAYMENT_VIEW',       1),
 (3, 'MEMBERSHIP_MANAGE',  1),
 (3, 'PT_MANAGE',          1);

-- ---------- Membership Plans ----------
INSERT INTO membership_plans (id, name, price, duration_days, description, status) VALUES
 (1, 'Gói 1 tháng',  500000.00,   30, 'Sử dụng phòng tập 30 ngày, được đặt lịch PT.',  'Active'),
 (2, 'Gói 3 tháng', 1350000.00,   90, 'Tiết kiệm 10% so với mua lẻ 3 tháng.',            'Active'),
 (3, 'Gói 6 tháng', 2500000.00,  180, 'Tiết kiệm 17%, ưu tiên đặt lịch PT.',             'Active'),
 (4, 'Gói 12 tháng',4500000.00,  365, 'Gói tốt nhất cho hội viên lâu dài.',              'Active'),
 (5, 'Gói thử 7 ngày', 99000.00,   7, 'Gói trải nghiệm - đã ngừng cung cấp.',            'Discontinued');

-- ---------- Membership mẫu: member1 đang Active (đã thanh toán), member2 chờ thanh toán ----------
INSERT INTO memberships (id, member_id, plan_id, plan_name_snapshot, price_snapshot, duration_days_snapshot,
                         start_date, end_date, status) VALUES
 (1, 7, 2, 'Gói 3 tháng', 1350000.00, 90, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 80 DAY), 'Active'),
 (2, 8, 1, 'Gói 1 tháng',  500000.00, 30, NULL, NULL, 'PendingPayment');

INSERT INTO payments (id, member_id, booking_id, membership_id, amount, status, transaction_code, paid_at) VALUES
 (1, 7, NULL, 1, 1350000.00, 'Success', 'DEMO-SEED-0001', DATE_SUB(NOW(), INTERVAL 10 DAY)),
 (2, 8, NULL, 2,  500000.00, 'Pending', NULL, NULL);

INSERT INTO membership_status_history (membership_id, old_status, new_status, reason, changed_by) VALUES
 (1, 'PendingPayment', 'Active', 'Thanh toán thành công', NULL);

-- ---------- Schedules: mỗi PT có các khung giờ 1 tiếng, từ ngày mai +2 đến +8 ----------
-- Khung giờ: 07:00, 08:00, 17:00, 18:00 (giờ UTC trong DB; chỉnh theo timezone nhóm quy ước)
-- Bắt đầu từ +2 ngày để thỏa quy tắc "đặt trước tối thiểu 24 giờ".
INSERT INTO schedules (pt_id, start_at, end_at, status)
WITH RECURSIVE d (n) AS (
  SELECT 2 UNION ALL SELECT n + 1 FROM d WHERE n < 8
),
h (hr) AS (
  SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 17 UNION ALL SELECT 18
)
SELECT p.id,
       TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL d.n DAY), MAKETIME(h.hr, 0, 0)),
       TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL d.n DAY), MAKETIME(h.hr + 1, 0, 0)),
       'Open'
FROM (SELECT 4 AS id UNION ALL SELECT 5 UNION ALL SELECT 6) p
CROSS JOIN d CROSS JOIN h;

-- ---------- Thông báo mẫu ----------
INSERT INTO notifications (user_id, type, title, content, reference_id) VALUES
 (7, 'Membership', 'Đăng ký gói thành công', 'Gói 3 tháng của bạn đã được kích hoạt.', 1),
 (8, 'Membership', 'Chờ thanh toán',         'Vui lòng thanh toán Gói 1 tháng để kích hoạt.', 2);

INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, new_value) VALUES
 (1, 'STAFF_PERMISSION_GRANT', 'user', 2, JSON_OBJECT('permission', 'REPORT_VIEW'));
