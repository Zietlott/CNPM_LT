<?php

require_once __DIR__ . '/../services/BookingService.php';
require_once __DIR__ . '/../helpers/auth.php';

class BookingController
{
    private BookingService $service;

    public function __construct(?BookingService $service = null)
    {
        $this->service = $service ?? new BookingService();
    }

    public function getAvailableTrainers(): array
    {
        return $this->service->getAvailableTrainers();
    }

    public function getAvailableSlots(int $trainerId, string $workDate): array
    {
        return $this->service->getAvailableSlots($trainerId, $workDate, $this->requireMemberId());
    }

    public function getMyBookings(): array
    {
        return $this->service->getMemberBookings($this->requireMemberId());
    }

    public function createMyBooking(int $scheduleId, string $note, string $startAt, string $endAt): int
    {
        return $this->service->createBooking(
            $this->requireMemberId(),
            $scheduleId,
            $note,
            $startAt,
            $endAt
        );
    }

    public function cancelMyBooking(int $bookingId, string $reason = ''): void
    {
        $this->service->cancelBooking(
            $this->requireMemberId(),
            $bookingId,
            $reason
        );
    }

    public function createMyChangeRequest(
        int $bookingId,
        int $scheduleId,
        string $reason,
        string $startAt,
        string $endAt
    ): int {
        return $this->service->createChangeRequest(
            $this->requireRole('member'),
            $bookingId,
            $scheduleId,
            $reason,
            $startAt,
            $endAt
        );
    }

    public function cancelMyChangeRequest(int $bookingId, int $requestId): void
    {
        $this->service->cancelChangeRequest(
            $this->requireRole('member'),
            $bookingId,
            $requestId
        );
    }

    public function getMyTrainerBookings(): array
    {
        return $this->service->getTrainerBookings($this->requireRole('trainer'));
    }

    public function getMyTrainerSchedules(): array
    {
        return $this->service->getTrainerSchedules($this->requireRole('trainer'));
    }

    public function createMyTrainerSchedule(
        string $workDate,
        string $startTime,
        string $endTime
    ): int {
        return $this->service->createTrainerSchedule(
            $this->requireRole('trainer'),
            $workDate,
            $startTime,
            $endTime
        );
    }

    public function createMyTrainerScheduleBatch(array $slots): array
    {
        return $this->service->createTrainerScheduleBatch(
            $this->requireRole('trainer'),
            $slots
        );
    }

    public function updateMyTrainerSchedule(
        int $scheduleId,
        string $workDate,
        string $startTime,
        string $endTime
    ): void {
        $this->service->updateTrainerSchedule(
            $this->requireRole('trainer'),
            $scheduleId,
            $workDate,
            $startTime,
            $endTime
        );
    }

    public function deactivateMyTrainerSchedule(int $scheduleId): void
    {
        $this->service->deactivateTrainerSchedule(
            $this->requireRole('trainer'),
            $scheduleId
        );
    }

    public function deleteMyTrainerSchedule(int $scheduleId): void
    {
        $this->service->deleteTrainerSchedule(
            $this->requireRole('trainer'),
            $scheduleId
        );
    }

    public function handleMyTrainerBooking(
        int $bookingId,
        string $action,
        string $reason = ''
    ): void {
        $this->service->updateTrainerBooking(
            $this->requireRole('trainer'),
            $bookingId,
            $action,
            $reason
        );
    }

    public function reviewMyChangeRequest(
        int $bookingId,
        int $requestId,
        bool $approve,
        string $reason = ''
    ): bool {
        return $this->service->reviewChangeRequest(
            $this->requireRole('trainer'),
            $bookingId,
            $requestId,
            $approve,
            $reason
        );
    }

    public function getAdminBookings(?string $status = null): array
    {
        $this->requireRole('admin');

        return $this->service->getAdminBookings($status);
    }

    public function getMyStaffBookings(): array
    {
        return $this->service->getStaffBookings($this->requireRole('staff'));
    }

    public function handleAdminBooking(
        int $bookingId,
        string $action,
        string $reason
    ): void {
        $actorId = $this->requireRole('admin');
        $this->service->updateBookingAsAdmin($actorId, $bookingId, $action, $reason);
    }

    public function handleStaffBooking(
        int $bookingId,
        string $action,
        string $reason
    ): void {
        $actorId = $this->requireRole('staff');
        $this->service->updateBookingAsStaff($actorId, $bookingId, $action, $reason);
    }

    public function getAdminBookingAccessData(): array
    {
        $this->requireRole('admin');

        return $this->service->getAdminBookingAccessData();
    }

    public function createTrainerAccount(
        int $trainerId,
        string $email,
        string $phone,
        string $password
    ): int {
        $this->requireRole('admin');

        return $this->service->createTrainerAccount(
            $trainerId,
            $email,
            $phone,
            $password
        );
    }

    public function createStaffAccount(
        string $fullName,
        string $email,
        string $phone,
        string $password,
        bool $canConfirm,
        bool $canCancel
    ): int {
        $actorId = $this->requireRole('admin');

        return $this->service->createStaffAccount(
            $fullName,
            $email,
            $phone,
            $password,
            $canConfirm,
            $canCancel,
            $actorId
        );
    }

    public function updateStaffPermissions(
        int $staffId,
        bool $canConfirm,
        bool $canCancel
    ): void {
        $actorId = $this->requireRole('admin');
        $this->service->updateStaffPermissions($staffId, $canConfirm, $canCancel, $actorId);
    }

    private function requireMemberId(): int
    {
        return $this->requireRole('member');
    }

    private function requireRole(string $role): int
    {
        if (!hasRole($role)) {
            throw new BookingException(
                'Bạn không có quyền thực hiện thao tác này.',
                'BOOKING_FORBIDDEN',
                401
            );
        }

        return (int) $_SESSION['member_id'];
    }
}
