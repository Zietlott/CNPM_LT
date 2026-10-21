<?php

require_once __DIR__ . '/../services/MembershipService.php';
require_once __DIR__ . '/../helpers/auth.php';

class MembershipController
{
    private MembershipService $service;

    public function __construct()
    {
        $this->service = new MembershipService();
    }

    // Lấy danh sách gói tập (không cần đăng nhập)
    public function getPlans(): array
    {
        try {
            return [
                'success' => true,
                'data' => $this->service->getAllPlans()
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Khong the lay danh sach goi tap'
            ];
        }
    }

    // Đăng ký gói cho hội viên đang đăng nhập
    public function registerPlan(int $planId): array
    {
        if (!isMember()) {
            return [
                'success' => false,
                'message' => 'Ban khong co quyen dang ky goi'
            ];
        }

        try {
            $memberId = (int) $_SESSION['member_id'];

            $membershipId = $this->service->registerMembership(
                $memberId,
                $planId
            );

            return [
                'success' => true,
                'membership_id' => $membershipId,
                'message' => 'Dang ky goi thanh cong, cho thanh toan'
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Khong the dang ky goi'
            ];
        }
    }

    // Xem lịch sử gói của chính mình
    public function getMyHistory(): array
    {
        if (!isMember()) {
            return [
                'success' => false,
                'message' => 'Ban chua dang nhap voi quyen hoi vien'
            ];
        }

        try {
            return [
                'success' => true,
                'data' => $this->service->getMembershipHistory(
                    (int) $_SESSION['member_id']
                )
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Khong the lay lich su dang ky'
            ];
        }
    }

    // Xem gói hiện tại của chính mình
    public function getMyCurrentMembership(): array
    {
        if (!isMember()) {
            return [
                'success' => false,
                'message' => 'Ban chua dang nhap voi quyen hoi vien'
            ];
        }

        try {
            return [
                'success' => true,
                'data' => $this->service->getCurrentMembership(
                    (int) $_SESSION['member_id']
                )
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Khong the lay goi hien tai'
            ];
        }
    }
}
