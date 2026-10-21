<?php

require_once __DIR__ . '/../config/database.php';

class AdminService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
    }

    // Lấy danh sách hội viên
    public function getAllMembers(): array
    {
        $sql = "SELECT member_id, full_name, email,
                       phone, role, status, created_at
                FROM member
                WHERE role = 'member'
                ORDER BY member_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }


    // Lấy thông tin chi tiết hội viên theo ID
    public function getMemberById(int $memberId): ?array
    {
        $sql = "SELECT member_id, full_name, email,
                       phone, role, status, created_at
                FROM member
                WHERE member_id = :member_id
                  AND role = 'member'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'member_id' => $memberId
        ]);

        $member = $stmt->fetch();

        return $member ?: null;
    }


    // Cập nhật thông tin hội viên
    public function updateMember(
        int $memberId,
        string $fullName,
        string $phone
    ): bool {
        $fullName = trim($fullName);
        $phone = trim($phone);

        if ($memberId <= 0 || $fullName === '' || $phone === '') {
            throw new InvalidArgumentException(
                'Thong tin hoi vien khong hop le'
            );
        }

        // Kiểm tra hội viên có tồn tại không
        if ($this->getMemberById($memberId) === null) {
            throw new InvalidArgumentException(
                'Khong tim thay hoi vien'
            );
        }

        // Kiểm tra số điện thoại có bị trùng không
        $sql = "SELECT member_id
                FROM member
                WHERE phone = :phone
                  AND member_id != :member_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'phone' => $phone,
            'member_id' => $memberId
        ]);

        if ($stmt->fetch()) {
            throw new InvalidArgumentException(
                'So dien thoai da duoc su dung'
            );
        }

        // Cập nhật thông tin
        $sql = "UPDATE member
                SET full_name = :full_name,
                    phone = :phone
                WHERE member_id = :member_id
                  AND role = 'member'";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'full_name' => $fullName,
            'phone' => $phone,
            'member_id' => $memberId
        ]);
    }


    // Khóa hoặc mở khóa tài khoản hội viên
    public function updateMemberStatus(
        int $memberId,
        string $status
    ): bool {
        // Chỉ cho phép hai trạng thái hợp lệ
        if (!in_array($status, ['active', 'locked'], true)) {
            throw new InvalidArgumentException(
                'Trang thai tai khoan khong hop le'
            );
        }

        // Kiểm tra hội viên tồn tại
        if ($this->getMemberById($memberId) === null) {
            throw new InvalidArgumentException(
                'Khong tim thay hoi vien'
            );
        }

        $sql = "UPDATE member
                SET status = :status
                WHERE member_id = :member_id
                  AND role = 'member'";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'status' => $status,
            'member_id' => $memberId
        ]);
    }

    // Xem chi tiết hội viên - chỉ Admin
    public function getMemberDetail(int $memberId): array
    {
        if (!isAdmin()) {
            return [
                'success' => false,
                'message' => 'Bạn không có quyền truy cập.'
            ];
        }

        if ($memberId <= 0) {
            return [
                'success' => false,
                'message' => 'ID hội viên không hợp lệ.'
            ];
        }

        $member = $this->service->getMemberById($memberId);

        if ($member === null) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy hội viên.'
            ];
        }

        return [
            'success' => true,
            'data' => $member
        ];
    }

}
