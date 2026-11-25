<?php
/**
 * GET /backend/api/earnings/admin-earnings.php
 * Admin/Staff xem thu nhập của tất cả Huấn luyện viên
 *
 * Query params:
 *   ?pt_id=<int>      (tuỳ chọn) – lọc theo PT cụ thể
 *   ?month=YYYY-MM    (tuỳ chọn) – lọc theo tháng
 *
 * Response:
 *   { success: true, data: { summary_by_pt: [...], rows: [...], trainers: [...], filters: {...} } }
 */

require_once __DIR__ . '/../../middleware/auth-middleware.php';
require_once __DIR__ . '/../../config/database.php';

AuthMiddleware::requireMethod('GET');
AuthMiddleware::requireRole(['admin', 'staff']);

$ptIdParam = isset($_GET['pt_id'])
    ? filter_var($_GET['pt_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : null;

$monthParam = $_GET['month'] ?? null;
$validMonth = null;
if ($monthParam !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)) {
    $validMonth = $monthParam;
}

try {
    $db = getDBConnection();

    $conditions = [];
    $bindParams = [];

    if ($ptIdParam !== null && $ptIdParam !== false) {
        $conditions[] = 'e.pt_id = :pt_id';
        $bindParams['pt_id'] = $ptIdParam;
    }
    if ($validMonth !== null) {
        $conditions[] = "DATE_FORMAT(e.recorded_at, '%Y-%m') = :month";
        $bindParams['month'] = $validMonth;
    }

    $where = count($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

    // Chi tiết các buổi tập đã hoàn thành có thu nhập
    $sqlRows = "SELECT
                    e.id                AS earning_id,
                    e.booking_id,
                    e.pt_id,
                    e.amount,
                    e.payment_status,
                    e.paid_at,
                    e.recorded_at,
                    b.start_at,
                    b.end_at,
                    b.duration_minutes,
                    pt.full_name        AS trainer_name,
                    m.full_name         AS member_name
                FROM pt_earnings e
                JOIN bookings b          ON b.id  = e.booking_id
                JOIN personal_trainer pt ON pt.trainer_id = e.pt_id
                JOIN users m             ON m.id  = b.member_id
                {$where}
                ORDER BY e.recorded_at DESC
                LIMIT 500";

    $stmtRows = $db->prepare($sqlRows);
    $stmtRows->execute($bindParams);
    $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê tổng hợp theo từng Huấn luyện viên
    $sqlSummary = "SELECT
                       e.pt_id,
                       pt.full_name               AS trainer_name,
                       COUNT(*)                   AS total_sessions,
                       COALESCE(SUM(e.amount), 0) AS total_earnings,
                       COALESCE(AVG(e.amount), 0) AS avg_per_session
                   FROM pt_earnings e
                   JOIN personal_trainer pt ON pt.trainer_id = e.pt_id
                   {$where}
                   GROUP BY e.pt_id, pt.full_name
                   ORDER BY total_earnings DESC";

    $stmtSum = $db->prepare($sqlSummary);
    $stmtSum->execute($bindParams);
    $summaryByPt = $stmtSum->fetchAll(PDO::FETCH_ASSOC);

    // Danh sách tất cả PT phục vụ dropdown bộ lọc
    $stmtPts = $db->query(
        "SELECT trainer_id, full_name FROM personal_trainer WHERE is_active = 1 ORDER BY full_name"
    );
    $trainers = $stmtPts->fetchAll(PDO::FETCH_ASSOC);

    $formattedRows = array_map(function ($r) {
        return [
            'earning_id'       => (int) $r['earning_id'],
            'booking_id'       => (int) $r['booking_id'],
            'pt_id'            => (int) $r['pt_id'],
            'amount'           => (float) $r['amount'],
            'recorded_at'      => $r['recorded_at'],
            'start_at'         => $r['start_at'],
            'end_at'           => $r['end_at'],
            'duration_minutes' => (int) $r['duration_minutes'],
            'trainer_name'     => $r['trainer_name'],
            'member_name'      => $r['member_name'],
            'payment_status'   => $r['payment_status'] ?? 'Pending',
            'paid_at'          => $r['paid_at'] ?? null,
        ];
    }, $rows);

    $formattedSummary = array_map(function ($r) {
        return [
            'pt_id'           => (int) $r['pt_id'],
            'trainer_name'    => $r['trainer_name'],
            'total_sessions'  => (int) $r['total_sessions'],
            'total_earnings'  => (float) $r['total_earnings'],
            'avg_per_session' => (float) $r['avg_per_session'],
        ];
    }, $summaryByPt);

    AuthMiddleware::json(true, 'Tải danh sách thu nhập thành công.', [
        'summary_by_pt' => $formattedSummary,
        'rows'          => $formattedRows,
        'trainers'      => $trainers,
        'filters'       => ['pt_id' => $ptIdParam, 'month' => $validMonth],
    ]);

} catch (Throwable $e) {
    AuthMiddleware::handleError($e, 'Không thể tải dữ liệu thu nhập.');
}
