<?php
/**
 * POST /backend/api/earnings/record.php
 * Ghi nhận / tính toán thu nhập cho Huấn luyện viên khi hoàn thành booking
 *
 * Payload:
 *   { "booking_id": 123 }
 *
 * Response:
 *   { success: true, message: "Đã ghi nhận thu nhập.", data: { earning_id, amount, ... } }
 */

require_once __DIR__ . '/../../middleware/auth-middleware.php';
require_once __DIR__ . '/../../config/database.php';

AuthMiddleware::requireMethod('POST');
$user = AuthMiddleware::requireRole(['trainer', 'admin', 'staff']);
AuthMiddleware::requireCsrf();
$input = AuthMiddleware::readJson();

$bookingId = filter_var(
    $input['booking_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$bookingId) {
    AuthMiddleware::json(false, 'Mã booking không hợp lệ.', null, 422, 'INVALID_BOOKING_ID');
}

try {
    $db = getDBConnection();

    // Lấy thông tin booking
    $stmt = $db->prepare(
        "SELECT id, pt_id, member_id, status, fee_snapshot, duration_minutes
         FROM bookings
         WHERE id = :booking_id
         LIMIT 1"
    );
    $stmt->execute(['booking_id' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        AuthMiddleware::json(false, 'Không tìm thấy thông tin booking.', null, 404, 'BOOKING_NOT_FOUND');
    }

    // Nếu người thực hiện là trainer, phải là PT của chính booking đó
    if ($user['role'] === 'trainer') {
        $ptStmt = $db->prepare(
            "SELECT trainer_id FROM personal_trainer WHERE member_id = :member_id LIMIT 1"
        );
        $ptStmt->execute(['member_id' => (int) $user['member_id']]);
        $pt = $ptStmt->fetch(PDO::FETCH_ASSOC);

        if (!$pt || (int) $booking['pt_id'] !== (int) $pt['trainer_id']) {
            AuthMiddleware::json(false, 'Bạn không có quyền ghi nhận thu nhập cho booking này.', null, 403, 'FORBIDDEN');
        }
    }

    // Chỉ ghi nhận khi booking đã ở trạng thái Completed/completed
    $statusLower = strtolower($booking['status']);
    if ($statusLower !== 'completed') {
        AuthMiddleware::json(false, 'Chỉ có thể ghi nhận thu nhập cho booking đã hoàn thành.', null, 409, 'BOOKING_NOT_COMPLETED');
    }

    $amount = (float) $booking['fee_snapshot'];
    $ptId = (int) $booking['pt_id'];

    // Kiểm tra xem đã ghi nhận chưa (idempotent EARN-02)
    $checkStmt = $db->prepare("SELECT id, amount, recorded_at FROM pt_earnings WHERE booking_id = :booking_id LIMIT 1");
    $checkStmt->execute(['booking_id' => $bookingId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        AuthMiddleware::json(true, 'Thu nhập của booking này đã được ghi nhận trước đó.', [
            'earning_id'  => (int) $existing['id'],
            'amount'      => (float) $existing['amount'],
            'recorded_at' => $existing['recorded_at'],
            'booking_id'  => $bookingId,
            'is_new'      => false,
        ]);
    }

    // Thêm mới vào pt_earnings (mặc định Pending)
    $paymentStatus = in_array($input['payment_status'] ?? '', ['Paid', 'Pending'], true) ? $input['payment_status'] : 'Pending';
    $paidAt = ($paymentStatus === 'Paid') ? date('Y-m-d H:i:s') : null;

    $insertStmt = $db->prepare(
        "INSERT INTO pt_earnings (pt_id, booking_id, amount, payment_status, paid_at, recorded_at)
         VALUES (:pt_id, :booking_id, :amount, :payment_status, :paid_at, NOW())"
    );
    $insertStmt->execute([
        'pt_id'          => $ptId,
        'booking_id'     => $bookingId,
        'amount'         => $amount,
        'payment_status' => $paymentStatus,
        'paid_at'        => $paidAt,
    ]);

    $newId = (int) $db->lastInsertId();

    AuthMiddleware::json(true, 'Đã ghi nhận thu nhập cho huấn luyện viên.', [
        'earning_id'     => $newId,
        'amount'         => $amount,
        'payment_status' => $paymentStatus,
        'booking_id'     => $bookingId,
        'is_new'         => true,
    ], 201);

} catch (Throwable $e) {
    AuthMiddleware::handleError($e, 'Lỗi khi ghi nhận thu nhập.');
}
