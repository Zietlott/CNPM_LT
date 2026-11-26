<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class MembershipService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Lấy danh sách gói đang được bán.
     */
    public function getAllPlans(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, price, duration_days, description, status
             FROM membership_plans
             WHERE status = 'Active'
             ORDER BY id ASC"
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đăng ký Membership mới.
     * Membership chỉ được kích hoạt sau khi thanh toán thành công.
     */
    public function registerMembership(
        int $memberId,
        int $planId
    ): int {
        if ($memberId <= 0 || $planId <= 0) {
            throw new InvalidArgumentException(
                'ID hoi vien hoac goi tap khong hop le'
            );
        }

        $this->db->beginTransaction();

        try {
            // Khóa tài khoản để tránh đăng ký đồng thời.
            $stmt = $this->db->prepare(
                "SELECT id
                 FROM users
                 WHERE id = ?
                   AND role = 'Member'
                   AND status = 'Active'
                 FOR UPDATE"
            );

            $stmt->execute([$memberId]);

            if (!$stmt->fetch()) {
                throw new InvalidArgumentException(
                    'Hoi vien khong ton tai hoac da bi khoa'
                );
            }

            // Kiểm tra và khóa gói tập.
            $stmt = $this->db->prepare(
                "SELECT id, name, price, duration_days
                 FROM membership_plans
                 WHERE id = ?
                   AND status = 'Active'
                 FOR UPDATE"
            );

            $stmt->execute([$planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan) {
                throw new InvalidArgumentException(
                    'Goi tap khong ton tai hoac da ngung ban'
                );
            }

            // Không cho đăng ký khi còn Membership PendingPayment hoặc Active.
            $stmt = $this->db->prepare(
                "SELECT id
                 FROM memberships
                 WHERE member_id = ?
                   AND status IN ('PendingPayment', 'Active')
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->execute([$memberId]);

            if ($stmt->fetch()) {
                throw new DomainException(
                    'Hoi vien dang co goi tap chua hoan tat'
                );
            }

            // Tạo Membership ở trạng thái chờ thanh toán.
            $stmt = $this->db->prepare(
                "INSERT INTO memberships (
                    member_id,
                    plan_id,
                    plan_name_snapshot,
                    price_snapshot,
                    duration_days_snapshot,
                    status
                ) VALUES (?, ?, ?, ?, ?, 'PendingPayment')"
            );

            $stmt->execute([
                $memberId,
                $plan['id'],
                $plan['name'],
                $plan['price'],
                $plan['duration_days']
            ]);

            $membershipId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return $membershipId;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Lấy lịch sử Membership.
     */
    public function getMembershipHistory(int $memberId): array
    {
        if ($memberId <= 0) {
            throw new InvalidArgumentException(
                'ID hoi vien khong hop le'
            );
        }

        $stmt = $this->db->prepare(
            "SELECT
                id AS membership_id,
                member_id,
                plan_id,
                plan_name_snapshot AS plan_name,
                price_snapshot AS price,
                duration_days_snapshot AS duration_days,
                GREATEST(
                    1,
                    ROUND(duration_days_snapshot / 30)
                ) AS duration_months,
                start_date,
                end_date,
                status,
                created_at
             FROM memberships
             WHERE member_id = ?
             ORDER BY created_at DESC, id DESC"
        );

        $stmt->execute([$memberId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy Membership đang có hiệu lực.
     */
    public function getCurrentMembership(int $memberId): ?array
    {
        if ($memberId <= 0) {
            throw new InvalidArgumentException(
                'ID hoi vien khong hop le'
            );
        }

        $stmt = $this->db->prepare(
            "SELECT
                id AS membership_id,
                member_id,
                plan_id,
                plan_name_snapshot AS plan_name,
                price_snapshot AS price,
                duration_days_snapshot AS duration_days,
                GREATEST(
                    1,
                    ROUND(duration_days_snapshot / 30)
                ) AS duration_months,
                start_date,
                end_date,
                status,
                created_at
             FROM memberships
             WHERE member_id = ?
               AND status = 'Active'
               AND start_date <= DATE(
                   UTC_TIMESTAMP() + INTERVAL 7 HOUR
               )
               AND end_date >= DATE(
                   UTC_TIMESTAMP() + INTERVAL 7 HOUR
               )
             ORDER BY end_date DESC, id DESC
             LIMIT 1"
        );

        $stmt->execute([$memberId]);

        $membership = $stmt->fetch(PDO::FETCH_ASSOC);

        return $membership ?: null;
    }
}
