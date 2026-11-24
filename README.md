# FitZone

## Chạy local bằng Docker

Mở Docker Desktop ở chế độ Linux containers. Từ thư mục dự án, chạy:

```sh
docker compose up -d --build
```

Mở [trang đăng nhập FitZone](http://localhost:8080/). Docker tự tạo database `fitness_center` và chạy `database/schema.sql` rồi `database/seed.sql` lần đầu khi volume còn trống.

Tài khoản demo gồm `member1@fitness.local`, `pt1@fitness.local`, `staff1@fitness.local` và `admin@fitness.local`. Mật khẩu chung là `Fitness@123`.

Sau khi sửa PHP hoặc CSS, chạy lại `docker compose up -d --build`. Nếu cổng `8080` đang bận, đặt biến môi trường `FITZONE_PORT` rồi chạy lại lệnh. Để kiểm tra trạng thái hoặc log, dùng `docker compose ps` và `docker compose logs web db`.

Dùng `docker compose down` để dừng. Dữ liệu được giữ trong volume `db_data`. Chỉnh file SQL chỉ ảnh hưởng đến lần khởi tạo volume mới.

Service `booking-worker` kiểm tra lượt giữ chỗ hết hạn và lịch không còn hợp lệ do vô hiệu hóa tài khoản, lưu trữ hồ sơ PT hoặc hủy Membership mỗi 30 giây. Khi chạy PHP ngoài Docker, có thể lên lịch chạy `php backend/jobs/expire-booking-holds.php` với cùng biến môi trường kết nối DB của ứng dụng.

### Cập nhật database Docker đã tạo trước bản chọn thời lượng

Chạy hai câu lệnh sau trong MariaDB trên database `fitness_center` để giữ dữ liệu hiện có. Lần cài mới bằng `schema.sql` đã có các thay đổi này.

```sql
USE fitness_center;
ALTER TABLE bookings
  DROP INDEX IF EXISTS uq_bookings_active_schedule,
  DROP COLUMN IF EXISTS active_schedule_id,
  ADD INDEX IF NOT EXISTS idx_bookings_schedule_time (schedule_id, start_at, end_at);
ALTER TABLE pt_profiles
  MODIFY approval_status ENUM('Pending','Approved','Rejected','Archived') NOT NULL DEFAULT 'Pending';
```
