# ERD – Fitness Center Membership & PT Booking App

Sơ đồ Mermaid (xem trực tiếp trên GitHub/GitLab hoặc dán vào https://mermaid.live để xuất PNG).

## Khởi tạo database cho dự án

Import [schema.sql](../database/schema.sql) vào MySQL/MariaDB để tạo database `fitness_center` trống, rồi import [seed.sql](../database/seed.sql) để thêm tài khoản và dữ liệu mẫu. PHP mặc định kết nối tới `127.0.0.1:3306`, user `root`, mật khẩu rỗng. Đặt `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` hoặc `DB_PASS` nếu môi trường của bạn dùng cấu hình khác.

Các view trong `schema.sql` cung cấp tên trường mà giao diện PHP hiện dùng. Dữ liệu được lưu trong các bảng ở sơ đồ dưới đây.

```mermaid
erDiagram
    users ||--o| member_profiles : "1-1 (Member)"
    users ||--o| pt_profiles : "1-1 (PT)"
    users ||--o{ staff_permissions : "staff_id"
    users ||--o{ auth_sessions : has
    users ||--o{ password_reset_tokens : has
    users ||--o{ notifications : receives
    users ||--o{ audit_logs : "actor_id"

    membership_plans ||--o{ memberships : "plan_id"
    users ||--o{ memberships : "member_id"
    memberships ||--o{ membership_status_history : logs
    memberships ||--o| memberships : "renewed_from_id"

    users ||--o{ schedules : "pt_id"
    users ||--o{ bookings : "member_id"
    users ||--o{ bookings : "pt_id"
    schedules ||--o{ bookings : "schedule_id"
    memberships ||--o{ bookings : "membership_id"

    bookings ||--o{ payments : "booking_id (nullable)"
    memberships ||--o{ payments : "membership_id (nullable)"
    payments ||--o| refunds : "payment_id (UNIQUE)"
    bookings ||--o{ refunds : "booking_id"

    bookings ||--o{ booking_change_requests : has
    schedules ||--o{ booking_change_requests : "new_schedule_id"

    bookings ||--o| pt_earnings : "booking_id (UNIQUE)"
    users ||--o{ pt_earnings : "pt_id"

    users {
        bigint id PK
        varchar email UK
        varchar username UK
        varchar password_hash
        varchar full_name
        enum role "Member|PT|Staff|Admin"
        enum status "Active|Locked|Disabled"
    }
    member_profiles {
        bigint user_id PK,FK
        date date_of_birth
        varchar emergency_contact
    }
    pt_profiles {
        bigint user_id PK,FK
        varchar specialty
        decimal hourly_rate
        enum approval_status
        bool accepting_bookings
    }
    staff_permissions {
        bigint id PK
        bigint staff_id FK
        varchar permission "UK(staff_id,permission)"
    }
    password_reset_tokens {
        bigint id PK
        bigint user_id FK
        char token_hash UK
        datetime expires_at
        datetime used_at
    }
    membership_plans {
        bigint id PK
        varchar name UK
        decimal price
        int duration_days
        enum status "Active|Discontinued"
    }
    memberships {
        bigint id PK
        bigint member_id FK
        bigint plan_id FK
        decimal price_snapshot
        date start_date
        date end_date
        enum status "PendingPayment|Active|Expired|Cancelled"
    }
    schedules {
        bigint id PK
        bigint pt_id FK
        datetime start_at "UK(pt_id,start_at)"
        datetime end_at
        enum status "Open|Closed"
    }
    bookings {
        bigint id PK
        bigint member_id FK
        bigint pt_id FK
        bigint schedule_id FK
        bigint membership_id FK
        decimal fee_snapshot
        varchar note
        enum status "AwaitingPayment|Pending|Confirmed|Completed|Expired|Cancelled|Rejected"
        datetime hold_expires_at
    }
    payments {
        bigint id PK
        bigint booking_id FK "NULL nếu là Membership"
        bigint membership_id FK "NULL nếu là Booking"
        decimal amount
        enum status "Pending|Success|Failed"
        varchar transaction_code UK
    }
    refunds {
        bigint id PK
        bigint payment_id FK,UK
        bigint booking_id FK
        decimal amount
        enum status
    }
    booking_change_requests {
        bigint id PK
        bigint booking_id FK
        bigint new_schedule_id FK
        varchar reason
        enum status "Pending|Approved|Rejected|Withdrawn"
    }
    pt_earnings {
        bigint id PK
        bigint pt_id FK
        bigint booking_id FK,UK
        decimal amount
    }
    notifications {
        bigint id PK
        bigint user_id FK
        bool is_read
    }
    audit_logs {
        bigint id PK
        bigint actor_id FK
        varchar action
        json old_value
        json new_value
    }
```

## Ràng buộc quan trọng đã cài ở tầng DB

| Quy tắc nghiệp vụ | Cách thực hiện trong schema |
|---|---|
| Không có 2 Booking cùng chiếm 1 slot PT | `bookings.active_schedule_id` (cột sinh) + `UNIQUE` – chỉ tính Booking ở trạng thái AwaitingPayment/Pending/Confirmed/Completed |
| Không có 2 Payment Pending cho cùng Booking/Membership | `payments.pending_booking_id`, `pending_membership_id` (cột sinh) + `UNIQUE` |
| Payment thuộc đúng 1 Membership **hoặc** 1 Booking | `CHECK ck_payments_one_target` |
| Chống hoàn tiền trùng | `refunds.payment_id` `UNIQUE` |
| Thu nhập PT ghi đúng 1 lần | `pt_earnings.booking_id` `UNIQUE` |
| Mỗi Member chỉ có 1 Membership Active | `memberships.active_member_id` (cột sinh) + `UNIQUE` |
| Mỗi Booking chỉ có 1 yêu cầu đổi lịch đang chờ | `booking_change_requests.pending_booking_id` + `UNIQUE` |
| Token đặt lại mật khẩu dùng 1 lần | `used_at` + `token_hash UNIQUE` (backend kiểm tra `used_at IS NULL`) |
| Khung giờ hợp lệ, không trùng | `CHECK start_at < end_at`, `UNIQUE(pt_id, start_at)` |
| Giữ nguyên lịch sử khi sửa gói | `memberships.*_snapshot` |

## Giả định thiết kế (cần cả nhóm thống nhất)

1. **Schedule = khung giờ cố định** (mặc định 1 tiếng) do PT tạo; 1 Booking chiếm đúng 1 Schedule. Nếu nhóm muốn đặt thời lượng linh hoạt (90 phút, 2 giờ…) thì cần đổi sang chống chồng lấn theo khoảng thời gian ở tầng Service.
2. **Giờ lưu DB là UTC**; frontend/backend chuyển sang giờ Việt Nam (UTC+7) khi hiển thị.
3. **Tối đa 1 Membership Active/Member**; gia hạn tạo Membership mới (`renewed_from_id`) và chỉ Active khi Payment Success.
4. **Cột sinh (generated column)** thay cho partial unique index vì MySQL/MariaDB không hỗ trợ partial index.
5. Quyền Staff lưu dạng chuỗi trong `staff_permissions.permission` (REPORT_VIEW, BOOKING_MANAGE, PAYMENT_VIEW, MEMBERSHIP_MANAGE, PT_MANAGE); danh sách chính thức do TV4 và TV3 chốt cùng middleware.
