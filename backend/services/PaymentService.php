<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

class PaymentService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    private function sameAmount(string $a, string $b): bool
    {
        // DECIMAL(12,2) values: normalize as strings, never use floats for money.
        $normalize = static function (string $value): string {
            if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                throw new DomainException('So tien khong hop le');
            }
            [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
            $whole = ltrim($whole, '0');
            return ($whole === '' ? '0' : $whole) . '.' . str_pad($fraction, 2, '0');
        };
        return $normalize($a) === $normalize($b);
    }

    public function getOrCreatePendingMembershipPayment(int $memberId, int $membershipId): array
    {
        if ($memberId <= 0 || $membershipId <= 0) {
            throw new InvalidArgumentException('Thong tin thanh toan khong hop le');
        }
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id, price_snapshot, status FROM memberships WHERE id = ? AND member_id = ? FOR UPDATE');
            $stmt->execute([$membershipId, $memberId]);
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$membership) {
                throw new InvalidArgumentException('Khong tim thay goi tap');
            }
            if ($membership['status'] !== 'PendingPayment') {
                throw new DomainException('Goi tap khong o trang thai cho thanh toan');
            }
            $stmt = $this->pdo->prepare("SELECT id, amount, status, transaction_code FROM payments WHERE membership_id = ? AND member_id = ? AND status = 'Pending' LIMIT 1 FOR UPDATE");
            $stmt->execute([$membershipId, $memberId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($payment) {
                if (!$this->sameAmount((string)$payment['amount'], (string)$membership['price_snapshot'])) {
                    throw new DomainException('So tien giao dich khong khop goi tap');
                }
                $this->pdo->commit();
                return $payment;
            }
            $stmt = $this->pdo->prepare("SELECT id FROM payments WHERE membership_id = ? AND status = 'Success' LIMIT 1");
            $stmt->execute([$membershipId]);
            if ($stmt->fetch()) {
                throw new DomainException('Goi tap da duoc thanh toan');
            }
            $stmt = $this->pdo->prepare("INSERT INTO payments (member_id, membership_id, amount, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$memberId, $membershipId, $membership['price_snapshot']]);
            $paymentId = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();
            return ['id' => $paymentId, 'amount' => $membership['price_snapshot'], 'status' => 'Pending', 'transaction_code' => null];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function settleDemoMembershipPayment(int $memberId, int $paymentId, bool $succeed): array
    {
        if ($memberId <= 0 || $paymentId <= 0) {
            throw new InvalidArgumentException('Thong tin thanh toan khong hop le');
        }
        $this->pdo->beginTransaction();
        try {
            // First read without locking to discover the membership; then lock
            // membership before payment, matching the create-payment lock order.
            $stmt = $this->pdo->prepare('SELECT membership_id FROM payments WHERE id = ? AND member_id = ? AND membership_id IS NOT NULL');
            $stmt->execute([$paymentId, $memberId]);
            $lookup = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lookup) throw new InvalidArgumentException('Khong tim thay giao dich');

            $stmt = $this->pdo->prepare('SELECT id, member_id, price_snapshot, duration_days_snapshot, status FROM memberships WHERE id = ? AND member_id = ? FOR UPDATE');
            $stmt->execute([(int)$lookup['membership_id'], $memberId]);
            $membership = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$membership) throw new InvalidArgumentException('Khong tim thay goi tap');

            $stmt = $this->pdo->prepare('SELECT id, member_id, membership_id, amount, status FROM payments WHERE id = ? AND member_id = ? FOR UPDATE');
            $stmt->execute([$paymentId, $memberId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$payment || (int)$payment['membership_id'] !== (int)$membership['id']) {
                throw new InvalidArgumentException('Khong tim thay giao dich');
            }
            if ($payment['status'] !== 'Pending') throw new DomainException('Giao dich da duoc xu ly');
            if ($membership['status'] !== 'PendingPayment') throw new DomainException('Goi tap khong cho thanh toan');
            if (!$this->sameAmount((string)$payment['amount'], (string)$membership['price_snapshot'])) {
                throw new DomainException('So tien thanh toan khong khop');
            }
            if ($succeed) {
                if ((int)$membership['duration_days_snapshot'] < 1) throw new DomainException('Thoi han goi tap khong hop le');
                $stmt = $this->pdo->prepare("SELECT id FROM memberships WHERE member_id = ? AND status = 'Active' LIMIT 1");
                $stmt->execute([$memberId]);
                if ($stmt->fetch()) throw new DomainException('Hoi vien da co goi tap Active');

                $stmt = $this->pdo->prepare("UPDATE payments SET status = 'Success', transaction_code = ?, paid_at = UTC_TIMESTAMP() WHERE id = ? AND status = 'Pending'");
                $stmt->execute(['DEMO-' . bin2hex(random_bytes(12)), $paymentId]);
                if ($stmt->rowCount() !== 1) throw new RuntimeException('Khong the cap nhat giao dich');

                $stmt = $this->pdo->prepare("UPDATE memberships SET status = 'Active', start_date = DATE(UTC_TIMESTAMP() + INTERVAL 7 HOUR), end_date = DATE_ADD(DATE(UTC_TIMESTAMP() + INTERVAL 7 HOUR), INTERVAL (duration_days_snapshot - 1) DAY) WHERE id = ? AND status = 'PendingPayment'");
                $stmt->execute([$membership['id']]);
                if ($stmt->rowCount() !== 1) throw new RuntimeException('Khong the kich hoat goi tap');

                $stmt = $this->pdo->prepare("INSERT INTO membership_status_history (membership_id, old_status, new_status, reason, changed_by) VALUES (?, 'PendingPayment', 'Active', ?, NULL)");
                $stmt->execute([$membership['id'], 'Demo payment successful']);
            } else {
                $stmt = $this->pdo->prepare("UPDATE payments SET status = 'Failed' WHERE id = ? AND status = 'Pending'");
                $stmt->execute([$paymentId]);
                if ($stmt->rowCount() !== 1) throw new RuntimeException('Khong the cap nhat giao dich');
            }
            $this->pdo->commit();
            return ['payment_id' => $paymentId, 'membership_id' => (int)$membership['id'], 'payment_status' => $succeed ? 'Success' : 'Failed', 'membership_status' => $succeed ? 'Active' : 'PendingPayment'];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getMemberPaymentHistory(int $memberId): array
    {
        if ($memberId <= 0) throw new InvalidArgumentException('Hoi vien khong hop le');
        $stmt = $this->pdo->prepare('SELECT p.id, p.membership_id, p.booking_id, p.amount, p.status, p.transaction_code, p.paid_at, p.created_at FROM payments p WHERE p.member_id = ? ORDER BY p.created_at DESC, p.id DESC LIMIT 100');
        $stmt->execute([$memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
