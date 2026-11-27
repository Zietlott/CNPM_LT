<?php

require_once __DIR__ . '/../services/AdminService.php';
require_once __DIR__ . '/../helpers/auth.php';

class AdminController
{
    private AdminService $service;

    public function __construct()
    {
        $this->service = new AdminService();
    }

    // Lấy danh sách hội viên - chỉ Admin
    public function getMembers(): array
    {
        if (!isAdmin()) {
            return [
                'success' => false,
                'message' => 'Bạn không có quyền truy cập.'
            ];
        }

        return [
            'success' => true,
            'data' => $this->service->getAllMembers()
        ];
    }


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


    // Cập nhật thông tin hội viên - chỉ Admin
    public function updateMember(
        int $memberId,
        string $fullName,
        string $phone
    ): array {
        if (!isAdmin()) {
            return [
                'success' => false,
                'message' => 'Bạn không có quyền truy cập.'
            ];
        }

        try {
            $actor = currentUser();

            if (!$actor || (int) $actor['member_id'] <= 0) {
                throw new RuntimeException('Không xác định được Admin.');
            }

            $updated = $this->service->updateMember(
                $memberId,
                $fullName,
                $phone,
                (int) $actor['member_id']
            );

            return [
                'success' => $updated,
                'message' => $updated
                    ? 'Cập nhật hội viên thành công.'
                    : 'Không thể cập nhật hội viên.'
            ];

        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Throwable $e) {
            error_log('Admin member operation failed: ' . $e->getMessage());
            throw $e;
        }
    }


    // Khóa hoặc mở khóa hội viên - chỉ Admin
    public function updateMemberStatus(
        int $memberId,
        string $status
    ): array {
        if (!isAdmin()) {
            return [
                'success' => false,
                'message' => 'Bạn không có quyền truy cập.'
            ];
        }

        try {
            $actor = currentUser();

            if (!$actor || (int) $actor['member_id'] <= 0) {
                throw new RuntimeException('Không xác định được Admin.');
            }

            $updated = $this->service->updateMemberStatus(
                $memberId,
                $status,
                (int) $actor['member_id']
            );

            return [
                'success' => $updated,
                'message' => $updated
                    ? 'Cập nhật trạng thái hội viên thành công.'
                    : 'Không thể cập nhật trạng thái hội viên.'
            ];

        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Throwable $e) {
            error_log('Admin member operation failed: ' . $e->getMessage());
            throw $e;
        }
    }

}
