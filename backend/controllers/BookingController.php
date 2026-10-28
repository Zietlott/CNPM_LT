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
        return $this->service->getAvailableSlots($trainerId, $workDate);
    }

    public function getMyBookings(): array
    {
        return $this->service->getMemberBookings($this->requireMemberId());
    }

    public function createMyBooking(int $scheduleId, string $note = ''): int
    {
        return $this->service->createBooking(
            $this->requireMemberId(),
            $scheduleId,
            $note
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

    private function requireMemberId(): int
    {
        if (!isMember()) {
            throw new BookingException(
                'Bạn cần đăng nhập bằng tài khoản hội viên.',
                'MEMBER_AUTHENTICATION_REQUIRED',
                401
            );
        }

        return (int) $_SESSION['member_id'];
    }
}