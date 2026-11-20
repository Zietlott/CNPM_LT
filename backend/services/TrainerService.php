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

}
