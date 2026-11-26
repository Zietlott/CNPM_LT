<?php
declare(strict_types=1);
require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../helpers/auth.php';

class PaymentController
{
    private PaymentService $service;
    public function __construct() { $this->service = new PaymentService(); }
    public function create(int $membershipId): array
    {
        return $this->service->getOrCreatePendingMembershipPayment((int)$_SESSION['member_id'], $membershipId);
    }
    public function payDemo(int $paymentId, bool $succeed): array
    {
        return $this->service->settleDemoMembershipPayment((int)$_SESSION['member_id'], $paymentId, $succeed);
    }
    public function history(): array
    {
        return $this->service->getMemberPaymentHistory((int)$_SESSION['member_id']);
    }
}
