<?php

require_once __DIR__ . '/../config/database.php';

class MembershipService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
    }

    // Lấy danh sách gói hội viên
    public function getAllPlans(): array
    {
        $sql = "SELECT *
                FROM membership_plan
                ORDER BY plan_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }


    // Đăng ký gói hội viên
    public function registerMembership(
        int $memberId,
        int $planId
    ): int {
        if ($memberId <= 0 || $planId <= 0) {
            throw new InvalidArgumentException(
                'ID hoi vien hoac goi tap khong hop le'
            );
        }

        // Kiểm tra hội viên đang hoạt động
        $sql = "SELECT member_id
                FROM member
                WHERE member_id = :member_id
                  AND role = 'member'
                  AND status = 'active'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['member_id' => $memberId]);

        if (!$stmt->fetch()) {
            throw new InvalidArgumentException(
                'Hoi vien khong ton tai hoac da bi khoa'
            );
        }

        // Kiểm tra gói tập có đang được bán không
        $sql = "SELECT plan_id
                FROM membership_plan
                WHERE plan_id = :plan_id
                  AND is_active = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['plan_id' => $planId]);

        if (!$stmt->fetch()) {
            throw new InvalidArgumentException(
                'Goi tap khong ton tai hoac da ngung ban'
            );
        }

        // Không cho đăng ký mới khi còn gói active hoặc pending
        $sql = "SELECT membership_id
                FROM membership
                WHERE member_id = :member_id
                  AND status IN ('pending', 'active')
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['member_id' => $memberId]);

        if ($stmt->fetch()) {
            throw new InvalidArgumentException(
                'Hoi vien dang co goi tap chua hoan tat'
            );
        }

        // Tạo đăng ký mới, chưa kích hoạt gói
        $sql = "INSERT INTO memberships
                    (member_id, plan_id, plan_name_snapshot, price_snapshot, duration_days_snapshot)
                SELECT :member_id, id, name, price, duration_days
                FROM membership_plans
                WHERE id = :plan_id AND status = 'Active'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'member_id' => $memberId,
            'plan_id' => $planId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new InvalidArgumentException('Goi tap khong ton tai hoac da ngung ban');
        }

        return (int) $this->db->lastInsertId();
    }


    // Lấy lịch sử đăng ký gói của một hội viên
    public function getMembershipHistory(int $memberId): array
    {
        if ($memberId <= 0) {
            throw new InvalidArgumentException(
                'ID hoi vien khong hop le'
            );
        }

        $sql = "SELECT
                    ms.membership_id,
                    ms.member_id,
                    ms.plan_id,
                    stored.plan_name_snapshot AS plan_name,
                    stored.price_snapshot AS price,
                    GREATEST(1, ROUND(stored.duration_days_snapshot / 30)) AS duration_months,
                    ms.start_date,
                    ms.end_date,
                    ms.status,
                    ms.created_at
                FROM membership ms
                INNER JOIN memberships stored ON stored.id = ms.membership_id
                WHERE ms.member_id = :member_id
                ORDER BY ms.created_at DESC,
                         ms.membership_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'member_id' => $memberId
        ]);

        return $stmt->fetchAll();
    }


    // Lấy gói hội viên đang có hiệu lực
    public function getCurrentMembership(int $memberId): ?array
    {
        if ($memberId <= 0) {
            throw new InvalidArgumentException(
                'ID hoi vien khong hop le'
            );
        }

        $sql = "SELECT
                    ms.membership_id,
                    ms.member_id,
                    ms.plan_id,
                    stored.plan_name_snapshot AS plan_name,
                    stored.price_snapshot AS price,
                    GREATEST(1, ROUND(stored.duration_days_snapshot / 30)) AS duration_months,
                    ms.start_date,
                    ms.end_date,
                    ms.status
                FROM membership ms
                INNER JOIN memberships stored ON stored.id = ms.membership_id
                WHERE ms.member_id = :member_id
                  AND ms.status = 'active'
                  AND ms.start_date <= DATE(UTC_TIMESTAMP() + INTERVAL 7 HOUR)
                  AND ms.end_date >= DATE(UTC_TIMESTAMP() + INTERVAL 7 HOUR)
                ORDER BY ms.end_date DESC,
                         ms.membership_id DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'member_id' => $memberId
        ]);

        $membership = $stmt->fetch();

        return $membership ?: null;
    }

}
