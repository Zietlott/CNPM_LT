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
        $sql = "INSERT INTO personal_trainer
                    (full_name, specialty, experience_years, description)
                VALUES
                    (:full_name, :specialty, :experience_years, :description)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':full_name' => $fullName,
            ':specialty' => $specialty,
            ':experience_years' => $experienceYears,
            ':description' => $description
        ]);

        return (int) $this->db->lastInsertId();
    }

    
    /**
     * Cập nhật thông tin huấn luyện viên.
     */
    public function updateTrainer(
        int $trainerId,
        string $fullName,
        string $specialty,
        int $experienceYears,
        ?string $description = null
    ): bool {
        $sql = "UPDATE personal_trainer
                SET full_name = :full_name,
                    specialty = :specialty,
                    experience_years = :experience_years,
                    description = :description
                WHERE trainer_id = :trainer_id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':trainer_id' => $trainerId,
            ':full_name' => $fullName,
            ':specialty' => $specialty,
            ':experience_years' => $experienceYears,
            ':description' => $description
        ]);
    }

    
    /**
     * Bật hoặc tắt trạng thái hoạt động của PT.
     */
    public function updateTrainerStatus(
        int $trainerId,
        bool $isActive
    ): bool {
        $sql = "UPDATE personal_trainer
                SET is_active = :is_active
                WHERE trainer_id = :trainer_id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':is_active',
            $isActive ? 1 : 0,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':trainer_id',
            $trainerId,
            PDO::PARAM_INT
        );

        return $stmt->execute();
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
