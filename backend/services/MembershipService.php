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

    /** Danh sách gói đang bán dành cho hội viên. */
    public function getAllPlans(): array
    {
        $stmt = $this->db->query(
            "SELECT id, name, price, duration_days, description, status
             FROM membership_plans
             WHERE status = 'Active'
             ORDER BY id ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Danh sách tất cả gói dành cho Admin/Staff có MEMBERSHIP_MANAGE. */
    public function getPlansForManagement(): array
    {
        return $this->db->query(
            "SELECT id, name, price, duration_days, description, status, created_at, updated_at
             FROM membership_plans
             ORDER BY status ASC, id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function savePlan(
        ?int $planId,
        string $name,
        string $price,
        int $durationDays,
        ?string $description,
        string $status = 'Active'
    ): int {
        $name = trim($name);
        $description = $description !== null ? trim($description) : null;
        if (
            $name === '' || mb_strlen($name) > 100 ||
            !preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $price) ||
            (float)$price < 0 ||
            $durationDays < 1 || $durationDays > 36500 ||
            ($description !== null && mb_strlen($description) > 500) ||
            !in_array($status, ['Active', 'Discontinued'], true)
        ) {
            throw new InvalidArgumentException('Thông tin gói tập không hợp lệ.');
        }

        if ($planId === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO membership_plans (name, price, duration_days, description, status)
                 VALUES (:name, :price, :duration_days, :description, :status)'
            );
            $stmt->execute([
                'name' => $name,
                'price' => $price,
                'duration_days' => $durationDays,
                'description' => $description,
                'status' => $status
            ]);
            return (int)$this->db->lastInsertId();
        }

        if ($planId <= 0) {
            throw new InvalidArgumentException('ID gói tập không hợp lệ.');
        }
        $stmt = $this->db->prepare(
            'UPDATE membership_plans
             SET name = :name, price = :price, duration_days = :duration_days,
                 description = :description, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $name,
            'price' => $price,
            'duration_days' => $durationDays,
            'description' => $description,
            'status' => $status,
            'id' => $planId
        ]);
        if ($stmt->rowCount() === 0) {
            $check = $this->db->prepare('SELECT id FROM membership_plans WHERE id = ?');
            $check->execute([$planId]);
            if (!$check->fetchColumn()) {
                throw new InvalidArgumentException('Không tìm thấy gói tập.');
            }
        }
        return $planId;
    }

    public function discontinuePlan(int $planId): void
    {
        if ($planId <= 0) {
            throw new InvalidArgumentException('ID gói tập không hợp lệ.');
        }
        $stmt = $this->db->prepare(
            "UPDATE membership_plans SET status = 'Discontinued' WHERE id = ?"
        );
        $stmt->execute([$planId]);
        if ($stmt->rowCount() === 0) {
            $check = $this->db->prepare('SELECT id FROM membership_plans WHERE id = ?');
            $check->execute([$planId]);
            if (!$check->fetchColumn()) {
                throw new InvalidArgumentException('Không tìm thấy gói tập.');
            }
        }
    }

    /**
     * Đăng ký mới: chỉ tạo PendingPayment. PaymentService chịu trách nhiệm kích hoạt.
     */
    public function registerMembership(int $memberId, int $planId): int
    {
        if ($memberId <= 0 || $planId <= 0) {
            throw new InvalidArgumentException('ID hội viên hoặc gói tập không hợp lệ.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM users
                 WHERE id = ? AND role = 'Member' AND status = 'Active'
                 FOR UPDATE"
            );
            $stmt->execute([$memberId]);
            if (!$stmt->fetch()) {
                throw new InvalidArgumentException('Hội viên không tồn tại hoặc đã bị khóa.');
            }

            $stmt = $this->db->prepare(
                "SELECT id, name, price, duration_days
                 FROM membership_plans
                 WHERE id = ? AND status = 'Active'
                 FOR UPDATE"
            );
            $stmt->execute([$planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan) {
                throw new InvalidArgumentException('Gói tập không tồn tại hoặc đã ngừng bán.');
            }

            $this->expirePastMemberships($memberId, null);

            $stmt = $this->db->prepare(
                "SELECT id FROM memberships
                 WHERE member_id = ? AND status IN ('PendingPayment', 'Active')
                 LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$memberId]);
            if ($stmt->fetch()) {
                throw new DomainException('Hội viên đang có gói tập chưa hoàn tất hoặc còn hiệu lực.');
            }

            $stmt = $this->db->prepare(
                "INSERT INTO memberships
                    (member_id, plan_id, plan_name_snapshot, price_snapshot, duration_days_snapshot, status)
                 VALUES (?, ?, ?, ?, ?, 'PendingPayment')"
            );
            $stmt->execute([
                $memberId, $plan['id'], $plan['name'], $plan['price'], $plan['duration_days']
            ]);
            $membershipId = (int)$this->db->lastInsertId();
            $this->recordStatus($membershipId, null, 'PendingPayment', 'Đăng ký gói tập', $memberId);
            $this->db->commit();
            return $membershipId;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    /**
     * Gia hạn sau khi Membership cũ hết hạn.
     * Schema hiện giới hạn một Membership Active/member; do đó không kích hoạt chồng lấn.
     */
    public function renewMembership(int $memberId, int $sourceMembershipId, int $planId): int
    {
        if ($memberId <= 0 || $sourceMembershipId <= 0 || $planId <= 0) {
            throw new InvalidArgumentException('Thông tin gia hạn không hợp lệ.');
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM users WHERE id = ? AND role = 'Member' AND status = 'Active' FOR UPDATE"
            );
            $stmt->execute([$memberId]);
            if (!$stmt->fetch()) throw new InvalidArgumentException('Tài khoản hội viên không hợp lệ.');

            $this->expirePastMemberships($memberId, $memberId);

            $stmt = $this->db->prepare(
                'SELECT id, status, end_date FROM memberships
                 WHERE id = ? AND member_id = ? FOR UPDATE'
            );
            $stmt->execute([$sourceMembershipId, $memberId]);
            $source = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$source) throw new InvalidArgumentException('Không tìm thấy Membership cần gia hạn.');
            if ($source['status'] !== 'Expired') {
                throw new DomainException('Chỉ có thể gia hạn sau khi Membership hiện tại hết hạn.');
            }

            $stmt = $this->db->prepare(
                "SELECT id FROM memberships
                 WHERE member_id = ? AND status IN ('PendingPayment', 'Active')
                 LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$memberId]);
            if ($stmt->fetch()) throw new DomainException('Hội viên đang có Membership chờ thanh toán hoặc còn hiệu lực.');

            $stmt = $this->db->prepare(
                "SELECT id, name, price, duration_days FROM membership_plans
                 WHERE id = ? AND status = 'Active' FOR UPDATE"
            );
            $stmt->execute([$planId]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan) throw new InvalidArgumentException('Gói tập không tồn tại hoặc đã ngừng bán.');

            $stmt = $this->db->prepare(
                "INSERT INTO memberships
                    (member_id, plan_id, plan_name_snapshot, price_snapshot, duration_days_snapshot, status, renewed_from_id)
                 VALUES (?, ?, ?, ?, ?, 'PendingPayment', ?)"
            );
            $stmt->execute([
                $memberId, $plan['id'], $plan['name'], $plan['price'],
                $plan['duration_days'], $sourceMembershipId
            ]);
            $newId = (int)$this->db->lastInsertId();
            $this->recordStatus($newId, null, 'PendingPayment', 'Đăng ký gia hạn', $memberId);
            $this->db->commit();
            return $newId;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function cancelMembership(
        int $membershipId,
        int $memberId,
        int $changedBy,
        bool $manageAnyMember = false,
        ?string $reason = null
    ): void {
        if ($membershipId <= 0 || $memberId <= 0 || $changedBy <= 0) {
            throw new InvalidArgumentException('Thông tin Membership không hợp lệ.');
        }
        $reason = $reason !== null ? trim($reason) : null;
        if ($reason !== null && mb_strlen($reason) > 255) {
            throw new InvalidArgumentException('Lý do hủy không được vượt quá 255 ký tự.');
        }

        $this->db->beginTransaction();
        try {
            $sql = 'SELECT id, member_id, status FROM memberships WHERE id = ?';
            $params = [$membershipId];
            if (!$manageAnyMember) {
                $sql .= ' AND member_id = ?';
                $params[] = $memberId;
            }
            $sql .= ' FOR UPDATE';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$membership) throw new InvalidArgumentException('Không tìm thấy Membership.');
            if (!in_array($membership['status'], ['PendingPayment', 'Active'], true)) {
                throw new DomainException('Membership không ở trạng thái có thể hủy.');
            }

            $oldStatus = $membership['status'];
            $stmt = $this->db->prepare(
                "UPDATE memberships SET status = 'Cancelled' WHERE id = ?"
            );
            $stmt->execute([$membershipId]);

            // Nếu đang chờ thanh toán thì vô hiệu hóa giao dịch Pending tương ứng.
            if ($oldStatus === 'PendingPayment') {
                $stmt = $this->db->prepare(
                    "UPDATE payments SET status = 'Failed'
                     WHERE membership_id = ? AND status = 'Pending'"
                );
                $stmt->execute([$membershipId]);
            }

            $this->recordStatus(
                $membershipId,
                $oldStatus,
                'Cancelled',
                $reason ?: 'Hủy Membership',
                $changedBy
            );
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function getMembershipHistory(int $memberId): array
    {
        if ($memberId <= 0) throw new InvalidArgumentException('ID hội viên không hợp lệ.');
        $stmt = $this->db->prepare(
            "SELECT id AS membership_id, member_id, plan_id,
                    plan_name_snapshot AS plan_name, price_snapshot AS price,
                    duration_days_snapshot AS duration_days,
                    GREATEST(1, ROUND(duration_days_snapshot / 30)) AS duration_months,
                    start_date, end_date, status, renewed_from_id, created_at
             FROM memberships WHERE member_id = ?
             ORDER BY created_at DESC, id DESC"
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCurrentMembership(int $memberId): ?array
    {
        if ($memberId <= 0) throw new InvalidArgumentException('ID hội viên không hợp lệ.');
        $this->expirePastMemberships($memberId, null);
        $stmt = $this->db->prepare(
            "SELECT id AS membership_id, member_id, plan_id,
                    plan_name_snapshot AS plan_name, price_snapshot AS price,
                    duration_days_snapshot AS duration_days,
                    GREATEST(1, ROUND(duration_days_snapshot / 30)) AS duration_months,
                    start_date, end_date, status, created_at
             FROM memberships
             WHERE member_id = ? AND status = 'Active'
               AND start_date <= DATE(UTC_TIMESTAMP() + INTERVAL 7 HOUR)
               AND end_date >= DATE(UTC_TIMESTAMP() + INTERVAL 7 HOUR)
             ORDER BY end_date DESC, id DESC LIMIT 1"
        );
        $stmt->execute([$memberId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Danh sách Membership cho Admin/Staff được cấp quyền. */
    public function getMembershipsForManagement(?string $status = null, ?int $memberId = null): array
    {
        $allowed = ['PendingPayment', 'Active', 'Expired', 'Cancelled'];
        if ($status !== null && !in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Trạng thái Membership không hợp lệ.');
        }
        $sql = "SELECT m.id AS membership_id, m.member_id, u.full_name AS member_name,
                       u.email, m.plan_id, m.plan_name_snapshot AS plan_name,
                       m.price_snapshot AS price, m.duration_days_snapshot AS duration_days,
                       m.start_date, m.end_date, m.status, m.renewed_from_id, m.created_at
                FROM memberships m
                INNER JOIN users u ON u.id = m.member_id";
        $where = [];
        $params = [];
        if ($status !== null) {
            $where[] = 'm.status = :status';
            $params['status'] = $status;
        }
        if ($memberId !== null) {
            if ($memberId <= 0) throw new InvalidArgumentException('ID hội viên không hợp lệ.');
            $where[] = 'm.member_id = :member_id';
            $params['member_id'] = $memberId;
        }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY m.created_at DESC, m.id DESC LIMIT 500';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function expirePastMemberships(int $memberId, ?int $changedBy): void
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM memberships
                 WHERE member_id = ? AND status = 'Active'
                   AND end_date < DATE(UTC_TIMESTAMP() + INTERVAL 7 HOUR)
                 FOR UPDATE"
            );
            $stmt->execute([$memberId]);
            $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            if ($ids) {
                $update = $this->db->prepare("UPDATE memberships SET status = 'Expired' WHERE id = ?");
                foreach ($ids as $id) {
                    $update->execute([$id]);
                    $this->recordStatus($id, 'Active', 'Expired', 'Membership hết hạn', $changedBy);
                }
            }
            if ($ownsTransaction) $this->db->commit();
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function recordStatus(
        int $membershipId,
        ?string $oldStatus,
        string $newStatus,
        string $reason,
        ?int $changedBy
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO membership_status_history
                (membership_id, old_status, new_status, reason, changed_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$membershipId, $oldStatus, $newStatus, $reason, $changedBy]);
    }
}
