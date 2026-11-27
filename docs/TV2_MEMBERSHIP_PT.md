# Bàn giao TV2 – Membership và Personal Trainer/Schedule

## API Membership

Các endpoint ghi dữ liệu yêu cầu session đăng nhập và header `X-CSRF-Token`.

| Endpoint | Method | Quyền | Mục đích |
|---|---|---|---|
| `membership/plans.php` | GET | Công khai | Xem gói đang bán |
| `membership/register.php` | POST | Member | Tạo Membership `PendingPayment` |
| `membership/current.php` | GET | Member | Xem Membership hiện tại |
| `membership/history.php` | GET | Member | Xem lịch sử Membership |
| `membership/renew.php` | POST | Member | Tạo yêu cầu gia hạn sau khi gói cũ hết hạn |
| `membership/cancel.php` | POST | Member | Hủy Membership PendingPayment/Active của chính mình |
| `membership/manage-plans.php` | GET/POST/PUT/PATCH | Admin hoặc Staff có `MEMBERSHIP_MANAGE` | Xem, thêm, sửa, ngừng bán gói |
| `membership/manage.php` | GET/POST | Admin hoặc Staff có `MEMBERSHIP_MANAGE` | Lọc Membership và hủy Membership |

### Luồng đăng ký/gia hạn

1. Gọi `membership/register.php` hoặc `membership/renew.php`.
2. Lấy `membership_id` trong phản hồi.
3. Gọi `payment/create.php` để tạo giao dịch.
4. Thanh toán qua module Payment hiện tại.
5. `PaymentService` kích hoạt Membership khi thanh toán thành công và ghi `membership_status_history`.

Membership chưa được kích hoạt khi mới đăng ký. Hủy Membership đang chờ thanh toán sẽ chuyển các giao dịch Pending liên quan sang Failed. Hủy Membership Active không tự động tạo hoàn tiền.

**Giới hạn gia hạn hiện tại:** Database có unique constraint chỉ cho phép một Membership Active trên mỗi hội viên. Vì vậy API chỉ cho gia hạn khi Membership nguồn đã hết hạn; không gia hạn sớm khi gói cũ vẫn Active để tránh kích hoạt hai gói chồng lấn hoặc làm mất thời gian sử dụng còn lại.

## API Personal Trainer

| Endpoint | Method | Quyền | Mục đích |
|---|---|---|---|
| `trainer/trainers.php` | GET | Công khai | Danh sách PT đang hoạt động |
| `trainer/trainer-detail.php?id={id}` | GET | Công khai | Chi tiết PT |
| `trainer/my-profile.php` | GET/PUT | PT | Xem/cập nhật hồ sơ PT của chính mình |
| `trainer/create-trainer.php` | POST | Admin hoặc Staff có `PT_MANAGE` | Tạo hồ sơ PT |
| `trainer/update-trainer.php` | POST | Admin hoặc Staff có `PT_MANAGE` | Cập nhật hồ sơ PT |
| `trainer/update-trainer-status.php` | POST | Admin hoặc Staff có `PT_MANAGE` | Bật/tắt nhận Booking |

PT tự cập nhật họ tên, chuyên môn, kinh nghiệm và giới thiệu. PT không thể tự đổi role, approval status, hourly rate hoặc quyền nhận Booking.

## Schedule và ngày nghỉ PT

- PT quản lý lịch qua `booking/trainer-schedules.php` (GET/POST/PUT/DELETE).
- Hội viên xem lịch trống qua `booking/availability.php`.
- Service kiểm tra khung giờ chồng lấn và từ chối tạo/sửa lịch trùng ngày nghỉ trong `trainer_leaves`.
- Availability loại bỏ slot trùng Booking đang Pending/Confirmed, slot đã đóng, slot quá khứ và slot nằm trong ngày nghỉ PT.
- Không cho sửa/khóa/xóa Schedule đã phát sinh Booking.

## Phân quyền Staff

`backend/helpers/permissions.php` cung cấp `hasStaffPermission()`. Admin được phép; Staff cần có permission trong `staff_permissions`.

Seed hiện tại đã cấp:
- Staff ID 2: `REPORT_VIEW`, `BOOKING_MANAGE`, `PAYMENT_VIEW`.
- Staff ID 3: `MEMBERSHIP_MANAGE`, `PT_MANAGE`.

## Kiểm thử

Chạy lint PHP từ thư mục gốc:

```powershell
Get-ChildItem .\backend -Recurse -Filter *.php |
    ForEach-Object { php -l $_.FullName }
```

Các kiểm thử DB phải chạy trên XAMPP với extension `pdo_mysql` và database `fitness_center` đã được khởi tạo. Môi trường kiểm tra trong phiên này không có extension `pdo_mysql`/MySQL server, vì vậy chưa thể xác nhận các luồng DB end-to-end tại đây.
