<?php

require_once __DIR__ . '/../services/MembershipService.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/permissions.php';

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
        } catch (InvalidArgumentException | DomainException $e) {
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


    public function managePlans(array $input, ?int $planId = null): array
    {
        if (!hasStaffPermission('MEMBERSHIP_MANAGE')) {
            return ['success' => false, 'message' => 'Bạn không có quyền quản lý gói tập.'];
        }
        try {
            $name = $input['name'] ?? null;
            $price = $input['price'] ?? null;
            $duration = filter_var($input['duration_days'] ?? null, FILTER_VALIDATE_INT);
            $description = $input['description'] ?? null;
            $status = $input['status'] ?? 'Active';
            if (!is_string($name) || !(is_string($price) || is_int($price) || is_float($price))
                || $duration === false || ($description !== null && !is_string($description))
                || !is_string($status)) {
                throw new InvalidArgumentException('Thông tin gói tập không hợp lệ.');
            }
            $id = $this->service->savePlan(
                $planId,
                $name,
                (string)$price,
                (int)$duration,
                $description,
                $status
            );
            return ['success' => true, 'message' => $planId ? 'Cập nhật gói tập thành công.' : 'Tạo gói tập thành công.', 'data' => ['plan_id' => $id]];
        } catch (Throwable $error) {
            return ['success' => false, 'message' => $error->getMessage()];
        }
    }

    public function discontinuePlan(int $planId): array
    {
        if (!hasStaffPermission('MEMBERSHIP_MANAGE')) {
            return ['success' => false, 'message' => 'Bạn không có quyền quản lý gói tập.'];
        }
        try {
            $this->service->discontinuePlan($planId);
            return ['success' => true, 'message' => 'Đã ngừng bán gói tập.'];
        } catch (Throwable $error) {
            return ['success' => false, 'message' => $error->getMessage()];
        }
    }

    public function getManagementPlans(): array
    {
        if (!hasStaffPermission('MEMBERSHIP_MANAGE')) {
            return ['success' => false, 'message' => 'Bạn không có quyền quản lý gói tập.'];
        }
        try {
            return ['success' => true, 'data' => $this->service->getPlansForManagement()];
        } catch (Throwable $error) {
            return ['success' => false, 'message' => 'Không thể lấy danh sách gói tập.'];
        }
    }

    public function renewMyMembership(int $sourceMembershipId, int $planId): array
    {
        if (!isMember()) return ['success' => false, 'message' => 'Bạn cần đăng nhập bằng tài khoản hội viên.'];
        try {
            $id = $this->service->renewMembership((int)$_SESSION['member_id'], $sourceMembershipId, $planId);
            return ['success' => true, 'membership_id' => $id, 'message' => 'Tạo yêu cầu gia hạn thành công. Vui lòng tiếp tục thanh toán.'];
        } catch (Throwable $error) {
            return ['success' => false, 'message' => $error->getMessage()];
        }
    }

    public function cancelMyMembership(int $membershipId, ?string $reason = null): array
    {
        if (!isMember()) return ['success' => false, 'message' => 'Bạn cần đăng nhập bằng tài khoản hội viên.'];
        try {
            $memberId = (int)$_SESSION['member_id'];
            $this->service->cancelMembership($membershipId, $memberId, $memberId, false, $reason);
            return ['success' => true, 'message' => 'Đã hủy Membership.'];
        } catch (Throwable $error) {
            return ['success' => false, 'message' => $error->getMessage()];
        }
    }

    public function getManagedMemberships(?string $status = null, ?int $memberId = null): array
    {
        if (!hasStaffPermission('MEMBERSHIP_MANAGE')) {
            return ['success' => false, 'message' => 'Bạn không có quyền quản lý Membership.'];
        }
        try {
            return ['success' => true, 'data' => $this->service->getMembershipsForManagement($status, $memberId)];
        } catch (Throwable $error) {
            return ['success' => false, 'message' => $error->getMessage()];
        }
    }

    public function cancelManagedMembership(int $membershipId, ?string $reason = null): array
    {
        if (!hasStaffPermission('MEMBERSHIP_MANAGE')) {
            return ['success' => false, 'message' => 'Bạn không có quyền quản lý Membership.'];
        }
        try {
            $actorId = (int)($_SESSION['member_id'] ?? 0);
            $this->service->cancelMembership($membershipId, $actorId, $actorId, true, $reason);
            return ['success' => true, 'message' => 'Đã hủy Membership.'];
        } catch (Throwable $error) {
            return ['success' => false, 'message' => $error->getMessage()];
        }
    }

}
