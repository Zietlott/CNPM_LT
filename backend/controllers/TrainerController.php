<?php

require_once __DIR__ . '/../services/TrainerService.php';

class TrainerController
{
    private TrainerService $trainerService;

    public function __construct()
    {
        $this->trainerService = new TrainerService();
    }

    public function getAllTrainers(): array
    {
        return [
            'success' => true,
            'message' => 'Lấy danh sách PT thành công',
            'data' => $this->trainerService->getAllTrainers()
        ];
    }

    public function getTrainerById(int $trainerId): array
    {
        $trainer = $this->trainerService->getTrainerById($trainerId);

        if ($trainer === null) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy huấn luyện viên'
            ];
        }

        return [
            'success' => true,
            'message' => 'Lấy thông tin PT thành công',
            'data' => $trainer
        ];
    }


    /**
     * Xử lý thêm huấn luyện viên mới.
     */
    public function createTrainer(
        string $fullName,
        string $specialty,
        int $experienceYears,
        ?string $description = null
    ): array {
        $fullName = trim($fullName);
        $specialty = trim($specialty);
        $description = $description !== null
            ? trim($description)
            : null;

        if ($fullName === '' || mb_strlen($fullName) > 100) {
            return [
                'success' => false,
                'message' => 'Tên huấn luyện viên không hợp lệ.'
            ];
        }

        if ($specialty === '' || mb_strlen($specialty) > 100) {
            return [
                'success' => false,
                'message' => 'Chuyên môn không hợp lệ.'
            ];
        }

        if ($experienceYears < 0 || $experienceYears > 255) {
            return [
                'success' => false,
                'message' => 'Số năm kinh nghiệm không hợp lệ.'
            ];
        }

        if ($description !== null && mb_strlen($description) > 255) {
            return [
                'success' => false,
                'message' => 'Mô tả không được vượt quá 255 ký tự.'
            ];
        }

        $trainerId = $this->trainerService->createTrainer(
            $fullName,
            $specialty,
            $experienceYears,
            $description
        );

        return [
            'success' => true,
            'message' => 'Thêm huấn luyện viên thành công.',
            'data' => [
                'trainer_id' => $trainerId
            ]
        ];
    }


    /**
     * Xử lý cập nhật thông tin huấn luyện viên.
     */
    public function updateTrainer(
        int $trainerId,
        string $fullName,
        string $specialty,
        int $experienceYears,
        ?string $description = null
    ): array {
        if ($trainerId <= 0) {
            return [
                'success' => false,
                'message' => 'ID huấn luyện viên không hợp lệ.'
            ];
        }

        // Kiểm tra PT có tồn tại trong database không.
        $trainer = $this->trainerService->getTrainerById($trainerId);

        if ($trainer === null) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy huấn luyện viên đang hoạt động.'
            ];
        }

        $fullName = trim($fullName);
        $specialty = trim($specialty);
        $description = $description !== null ? trim($description) : null;

        if ($fullName === '' || mb_strlen($fullName) > 100) {
            return [
                'success' => false,
                'message' => 'Tên huấn luyện viên không hợp lệ.'
            ];
        }

        if ($specialty === '' || mb_strlen($specialty) > 100) {
            return [
                'success' => false,
                'message' => 'Chuyên môn không hợp lệ.'
            ];
        }

        if ($experienceYears < 0 || $experienceYears > 255) {
            return [
                'success' => false,
                'message' => 'Số năm kinh nghiệm không hợp lệ.'
            ];
        }

        if ($description !== null && mb_strlen($description) > 255) {
            return [
                'success' => false,
                'message' => 'Mô tả không được vượt quá 255 ký tự.'
            ];
        }

        $updated = $this->trainerService->updateTrainer(
            $trainerId,
            $fullName,
            $specialty,
            $experienceYears,
            $description
        );

        return [
            'success' => $updated,
            'message' => $updated
                ? 'Cập nhật huấn luyện viên thành công.'
                : 'Không thể cập nhật huấn luyện viên.'
        ];
    }


    public function updateTrainerStatus(
        int $trainerId,
        bool $isActive
    ): array {
        if ($trainerId <= 0) {
            return [
                'success' => false,
                'message' => 'ID huấn luyện viên không hợp lệ.'
            ];
        }

        // Kiểm tra PT tồn tại, kể cả PT đang bị tắt
        $trainer = $this->trainerService->findTrainerById($trainerId);

        if ($trainer === null) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy huấn luyện viên.'
            ];
        }

        $updated = $this->trainerService->updateTrainerStatus(
            $trainerId,
            $isActive
        );

        return [
            'success' => $updated,
            'message' => $updated
                ? 'Cập nhật trạng thái PT thành công.'
                : 'Không thể cập nhật trạng thái PT.'
        ];
    }

}
