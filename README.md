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
