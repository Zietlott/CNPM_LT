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
        $sql = "SELECT id AS member_id, full_name, email,
                       phone, role, status, created_at
                FROM users
                WHERE role = 'Member'
                ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }


    // Lấy thông tin chi tiết hội viên theo ID
    public function getMemberById(int $memberId): ?array
    {
        $sql = "SELECT id AS member_id, full_name, email,
                       phone, role, status, created_at
                FROM users
                WHERE id = :member_id
                  AND role = 'Member'
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
        string $phone,
        int $actorId
    ): bool {
        $fullName = trim($fullName);
        $phone = trim($phone);

        if ($memberId <= 0 || $fullName === '' || $phone === '') {
            throw new InvalidArgumentException(
                'Thong tin hoi vien khong hop le'
            );
        }

        if ($actorId <= 0) {
            throw new InvalidArgumentException(
                'Nguoi thuc hien khong hop le'
            );
        }

        $ownsTransaction = !$this->db->inTransaction();

        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT id, full_name, phone
                 FROM users
                 WHERE id = :member_id AND role = 'Member'
                 FOR UPDATE"
            );
            $stmt->execute(['member_id' => $memberId]);
            $before = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$before) {
                throw new InvalidArgumentException(
                    'Khong tim thay hoi vien'
                );
            }

            $stmt = $this->db->prepare(
                "SELECT id FROM users
                 WHERE phone = :phone AND id != :member_id
                 LIMIT 1"
            );
            $stmt->execute([
                'phone' => $phone,
                'member_id' => $memberId
            ]);

            if ($stmt->fetch()) {
                throw new InvalidArgumentException(
                    'So dien thoai da duoc su dung'
                );
            }

            $changed = $before['full_name'] !== $fullName
                || $before['phone'] !== $phone;

            if (!$changed) {
                if ($ownsTransaction) {
                    $this->db->commit();
                }
                return true;
            }

            $stmt = $this->db->prepare(
                "UPDATE users
                 SET full_name = :full_name, phone = :phone
                 WHERE id = :member_id AND role = 'Member'"
            );

            $stmt->execute([
                'full_name' => $fullName,
                'phone' => $phone,
                'member_id' => $memberId
            ]);

            $this->recordMemberAudit(
                $actorId,
                $memberId,
                'MEMBER_UPDATE',
                [
                    'full_name' => $before['full_name'],
                    'phone' => $before['phone']
                ],
                [
                    'full_name' => $fullName,
                    'phone' => $phone
                ]
            );

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return true;

        } catch (Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    // Khóa hoặc mở khóa tài khoản hội viên
    public function updateMemberStatus(
        int $memberId,
        string $status,
        int $actorId
    ): bool {
        if ($memberId <= 0 || !in_array($status, ['Active', 'Locked'], true)) {
            throw new InvalidArgumentException(
                'Trang thai tai khoan khong hop le'
            );
        }

        if ($actorId <= 0) {
            throw new InvalidArgumentException(
                'Nguoi thuc hien khong hop le'
            );
        }

        $ownsTransaction = !$this->db->inTransaction();

        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT status FROM users
                 WHERE id = :member_id AND role = 'Member'
                 FOR UPDATE"
            );
            $stmt->execute(['member_id' => $memberId]);

            $before = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$before) {
                throw new InvalidArgumentException(
                    'Khong tim thay hoi vien'
                );
            }

            if ($before['status'] === $status) {
                if ($ownsTransaction) {
                    $this->db->commit();
                }
                return true;
            }

            $stmt = $this->db->prepare(
                "UPDATE users SET status = :status
                 WHERE id = :member_id AND role = 'Member'"
            );

            $stmt->execute([
                'status' => $status,
                'member_id' => $memberId
            ]);

            $this->recordMemberAudit(
                $actorId,
                $memberId,
                'MEMBER_STATUS_CHANGE',
                ['status' => $before['status']],
                ['status' => $status]
            );

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return true;

        } catch (Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    private function recordMemberAudit(
        int $actorId,
        int $memberId,
        string $action,
        array $before,
        array $after
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs
                (actor_id, action, entity_type, entity_id,
                 old_value, new_value)
             VALUES
                (:actor_id, :action, 'Member', :entity_id,
                 :old_value, :new_value)"
        );

        $stmt->execute([
            'actor_id' => $actorId,
            'action' => $action,
            'entity_id' => $memberId,
            'old_value' => json_encode(
                $before,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            ),
            'new_value' => json_encode(
                $after,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            )
        ]);
    }

}
