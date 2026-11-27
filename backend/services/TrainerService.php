<?php

require_once __DIR__ . '/../config/database.php';

class TrainerService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }


    /**
     * Lấy danh sách PT đang hoạt động.
     */
    public function getAllTrainers(): array
    {
        $sql = "SELECT
                    trainer_id,
                    full_name,
                    specialty,
                    experience_years,
                    description,
                    initials,
                    color_class,
                    is_active
                FROM personal_trainer
                WHERE is_active = 1
                ORDER BY trainer_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Lấy thông tin chi tiết một PT đang hoạt động.
     */
    public function getTrainerById(int $trainerId): ?array
    {
        $sql = "SELECT
                    trainer_id,
                    full_name,
                    specialty,
                    experience_years,
                    description,
                    initials,
                    color_class,
                    is_active
                FROM personal_trainer
                WHERE trainer_id = :trainer_id
                AND is_active = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':trainer_id', $trainerId, PDO::PARAM_INT);
        $stmt->execute();

        $trainer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $trainer ?: null;
    }


    /**
     * Thêm huấn luyện viên mới.
     */
    public function createTrainer(
        string $fullName,
        string $specialty,
        int $experienceYears,
        ?string $description = null
    ): int {
        $this->db->beginTransaction();
        try {
            $user = $this->db->prepare(
                "INSERT INTO users (full_name, password_hash, role) VALUES (?, ?, 'PT')"
            );
            $user->execute([$fullName, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]);
            $trainerId = (int) $this->db->lastInsertId();
            $profile = $this->db->prepare(
                "INSERT INTO pt_profiles (user_id, specialty, experience_years, bio, hourly_rate, approval_status)
                 VALUES (?, ?, ?, ?, NULL, 'Approved')"
            );
            $profile->execute([$trainerId, $specialty, $experienceYears, $description]);
            $this->db->commit();
            return $trainerId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateTrainer(
        int $trainerId,
        string $fullName,
        string $specialty,
        int $experienceYears,
        ?string $description = null
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE pt_profiles p JOIN users u ON u.id = p.user_id
             SET u.full_name = ?, p.specialty = ?, p.experience_years = ?, p.bio = ?
             WHERE p.user_id = ?"
        );
        return $stmt->execute([$fullName, $specialty, $experienceYears, $description, $trainerId]);
    }

    public function updateTrainerStatus(int $trainerId, bool $isActive): bool
    {
        $stmt = $this->db->prepare("UPDATE pt_profiles SET accepting_bookings = ? WHERE user_id = ?");
        return $stmt->execute([(int) $isActive, $trainerId]);
    }

    public function findTrainerById(int $trainerId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT trainer_id, is_active
            FROM personal_trainer
            WHERE trainer_id = :trainer_id
            LIMIT 1"
        );

        $stmt->execute([':trainer_id' => $trainerId]);

        $trainer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $trainer ?: null;
    }



    /**
     * PT tự xem hồ sơ tài khoản của mình.
     */
    public function getOwnProfile(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id AS trainer_id, u.full_name, u.email, u.phone,
                    p.specialty, p.experience_years, p.bio AS description,
                    p.hourly_rate, p.approval_status, p.accepting_bookings
             FROM users u
             INNER JOIN pt_profiles p ON p.user_id = u.id
             WHERE u.id = ? AND u.role = 'PT' AND u.status = 'Active'
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        return $profile ?: null;
    }

    /**
     * PT chỉ được cập nhật thông tin công khai của chính mình.
     * Không cho phép thay đổi role, trạng thái duyệt, đơn giá hoặc quyền nhận booking.
     */
    public function updateOwnProfile(
        int $userId,
        string $fullName,
        string $specialty,
        int $experienceYears,
        ?string $bio
    ): bool {
        $fullName = trim($fullName);
        $specialty = trim($specialty);
        $bio = $bio !== null ? trim($bio) : null;

        if ($userId <= 0 || $fullName === '' || mb_strlen($fullName) > 150
            || $specialty === '' || mb_strlen($specialty) > 255
            || $experienceYears < 0 || $experienceYears > 255
            || ($bio !== null && mb_strlen($bio) > 5000)) {
            throw new InvalidArgumentException('Thông tin hồ sơ PT không hợp lệ.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT u.id
                 FROM users u
                 INNER JOIN pt_profiles p ON p.user_id = u.id
                 WHERE u.id = ? AND u.role = 'PT' AND u.status = 'Active'
                 FOR UPDATE"
            );
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                throw new InvalidArgumentException('Không tìm thấy tài khoản PT đang hoạt động.');
            }

            $stmt = $this->db->prepare(
                'UPDATE users SET full_name = ? WHERE id = ?'
            );
            $stmt->execute([$fullName, $userId]);

            $stmt = $this->db->prepare(
                'UPDATE pt_profiles
                 SET specialty = ?, experience_years = ?, bio = ?
                 WHERE user_id = ?'
            );
            $stmt->execute([$specialty, $experienceYears, $bio, $userId]);
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

}
