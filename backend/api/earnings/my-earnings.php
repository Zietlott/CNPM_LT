<?php
/**
 * GET /backend/api/earnings/my-earnings.php
 * Thu nhập của Huấn luyện viên (trainer xem thu nhập của chính mình)
 *
 * Query params:
 *   ?month=YYYY-MM       (tuỳ chọn) – lọc theo tháng, để trống để xem tất cả
 *   ?status=Paid|Pending (tuỳ chọn) – lọc theo trạng thái thanh toán
 *   ?search=xxx          (tuỳ chọn) – tìm kiếm theo tên hoặc SĐT hội viên
 *
 * Response:
 *   {
 *     success: true,
 *     data: {
 *       summary: {
 *         total_earnings: float,
 *         paid_earnings: float,
 *         pending_earnings: float,
 *         total_sessions: int,
 *         avg_per_session: float
 *       },
 *       chart: [
 *         { month: "YYYY-MM", label: "TXX/YYYY", total_earnings: float, paid_earnings: float, pending_earnings: float, sessions_count: int },
 *         ...
 *       ],
 *       rows: [
 *         {
 *           earning_id: int,
 *           booking_id: int,
 *           session_date: string,
 *           session_time: string,
 *           start_at: string,
 *           end_at: string,
 *           duration_minutes: int,
 *           member_id: int,
 *           member_name: string,
 *           member_phone: string,
 *           member_email: string,
 *           sessions_count: int,
 *           amount: float,
 *           fee_snapshot: float,
 *           payment_status: "Paid"|"Pending",
 *           payment_status_text: "Đã thanh toán"|"Chờ thanh toán",
 *           recorded_at: string,
 *           paid_at: string|null,
 *           note: string|null
 *         }
 *       ],
 *       filters: {
 *         month: string|null,
 *         status: string,
 *         search: string
 *       }
 *     }
 *   }
 */

require_once __DIR__ . '/../../middleware/auth-middleware.php';
require_once __DIR__ . '/../../config/database.php';

AuthMiddleware::requireMethod('GET');
$user = AuthMiddleware::requireRole('trainer');

$monthParam = trim($_GET['month'] ?? '');
$validMonth = null;
if ($monthParam !== '' && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)) {
    $validMonth = $monthParam;
}

$statusParam = trim($_GET['status'] ?? '');
$validStatus = null;
if (in_array($statusParam, ['Paid', 'Pending'], true)) {
    $validStatus = $statusParam;
}

$searchParam = trim($_GET['search'] ?? '');

try {
    $db = getDBConnection();

    // 1. Xác thực trainer_id từ user_id (member_id) trong session
    $stmtPt = $db->prepare(
        "SELECT trainer_id FROM personal_trainer WHERE member_id = :member_id LIMIT 1"
    );
    $stmtPt->execute(['member_id' => (int) $user['member_id']]);
    $ptRow = $stmtPt->fetch(PDO::FETCH_ASSOC);

    if (!$ptRow) {
        AuthMiddleware::json(false, 'Không tìm thấy thông tin huấn luyện viên.', null, 404, 'TRAINER_NOT_FOUND');
    }

    $trainerId = (int) $ptRow['trainer_id'];

    // 2. Xây dựng điều kiện lọc tháng cho thống kê KPI
    $kpiMonthFilter = '';
    $kpiParams = ['trainer_id' => $trainerId];
    if ($validMonth !== null) {
        $kpiMonthFilter = " AND DATE_FORMAT(e.recorded_at, '%Y-%m') = :month";
        $kpiParams['month'] = $validMonth;
    }

    // 4 THẺ THỐNG KÊ (Tổng thu nhập, Đã thanh toán, Chờ thanh toán, Số buổi hoàn thành)
    $sqlSummary = "SELECT
                       COALESCE(SUM(e.amount), 0) AS total_earnings,
                       COALESCE(SUM(CASE WHEN e.payment_status = 'Paid' THEN e.amount ELSE 0 END), 0) AS paid_earnings,
                       COALESCE(SUM(CASE WHEN e.payment_status = 'Pending' THEN e.amount ELSE 0 END), 0) AS pending_earnings,
                       COUNT(*)                   AS total_sessions,
                       COALESCE(AVG(e.amount), 0) AS avg_per_session
                   FROM pt_earnings e
                   WHERE e.pt_id = :trainer_id
                   {$kpiMonthFilter}";

    $stmtSum = $db->prepare($sqlSummary);
    $stmtSum->execute($kpiParams);
    $summary = $stmtSum->fetch(PDO::FETCH_ASSOC) ?: [
        'total_earnings'   => 0,
        'paid_earnings'    => 0,
        'pending_earnings' => 0,
        'total_sessions'   => 0,
        'avg_per_session'  => 0,
    ];

    // 3. BIỂU ĐỒ THU NHẬP 6 THÁNG GẦN NHẤT
    // Xác định mốc 6 tháng kết thúc tại tháng hiện tại (hoặc tháng lọc nếu lớn hơn)
    $endMonthDate = new DateTime();
    if ($validMonth !== null) {
        $filterDate = DateTime::createFromFormat('Y-m-d', $validMonth . '-01');
        if ($filterDate && $filterDate > $endMonthDate) {
            $endMonthDate = $filterDate;
        }
    }
    // Lấy ngày cuối của tháng kết thúc
    $endMonthDate->modify('last day of this month')->setTime(23, 59, 59);
    $startMonthDate = clone $endMonthDate;
    $startMonthDate->modify('-5 months')->modify('first day of this month')->setTime(0, 0, 0);

    $sqlChart = "SELECT
                     DATE_FORMAT(e.recorded_at, '%Y-%m') AS month_key,
                     COALESCE(SUM(e.amount), 0) AS total_earnings,
                     COALESCE(SUM(CASE WHEN e.payment_status = 'Paid' THEN e.amount ELSE 0 END), 0) AS paid_earnings,
                     COALESCE(SUM(CASE WHEN e.payment_status = 'Pending' THEN e.amount ELSE 0 END), 0) AS pending_earnings,
                     COUNT(*) AS sessions_count
                 FROM pt_earnings e
                 WHERE e.pt_id = :trainer_id
                   AND e.recorded_at >= :start_date
                   AND e.recorded_at <= :end_date
                 GROUP BY DATE_FORMAT(e.recorded_at, '%Y-%m')
                 ORDER BY month_key ASC";

    $stmtChart = $db->prepare($sqlChart);
    $stmtChart->execute([
        'trainer_id' => $trainerId,
        'start_date' => $startMonthDate->format('Y-m-d H:i:s'),
        'end_date'   => $endMonthDate->format('Y-m-d H:i:s'),
    ]);
    $chartRaw = $stmtChart->fetchAll(PDO::FETCH_ASSOC);

    // Map kết quả thành dictionary theo month_key
    $chartDict = [];
    foreach ($chartRaw as $cr) {
        $chartDict[$cr['month_key']] = $cr;
    }

    // Sinh đúng 6 tháng liên tục để biểu đồ không bị khuyết
    $chartData = [];
    $iterDate = clone $startMonthDate;
    while ($iterDate <= $endMonthDate) {
        $mKey = $iterDate->format('Y-m');
        $label = 'T' . $iterDate->format('m/Y');
        if (isset($chartDict[$mKey])) {
            $chartData[] = [
                'month'            => $mKey,
                'label'            => $label,
                'total_earnings'   => (float) $chartDict[$mKey]['total_earnings'],
                'paid_earnings'    => (float) $chartDict[$mKey]['paid_earnings'],
                'pending_earnings' => (float) $chartDict[$mKey]['pending_earnings'],
                'sessions_count'   => (int) $chartDict[$mKey]['sessions_count'],
            ];
        } else {
            $chartData[] = [
                'month'            => $mKey,
                'label'            => $label,
                'total_earnings'   => 0.0,
                'paid_earnings'    => 0.0,
                'pending_earnings' => 0.0,
                'sessions_count'   => 0,
            ];
        }
        $iterDate->modify('+1 month');
    }

    // 4. DANH SÁCH CHI TIẾT (LỊCH SỬ THU NHẬP)
    $rowConditions = ['e.pt_id = :trainer_id'];
    $rowParams = ['trainer_id' => $trainerId];

    if ($validMonth !== null) {
        $rowConditions[] = "DATE_FORMAT(e.recorded_at, '%Y-%m') = :row_month";
        $rowParams['row_month'] = $validMonth;
    }

    if ($validStatus !== null) {
        $rowConditions[] = "e.payment_status = :row_status";
        $rowParams['row_status'] = $validStatus;
    }

    if ($searchParam !== '') {
        $rowConditions[] = "(m.full_name LIKE :search_name OR m.phone LIKE :search_phone)";
        $rowParams['search_name']  = '%' . $searchParam . '%';
        $rowParams['search_phone'] = '%' . $searchParam . '%';
    }

    $whereClause = ' WHERE ' . implode(' AND ', $rowConditions);

    $sqlRows = "SELECT
                    e.id                AS earning_id,
                    e.booking_id,
                    e.amount,
                    e.payment_status,
                    e.paid_at,
                    e.recorded_at,
                    b.start_at,
                    b.end_at,
                    b.duration_minutes,
                    b.fee_snapshot,
                    b.note              AS booking_note,
                    b.status            AS booking_status,
                    m.id                AS member_id,
                    m.full_name         AS member_name,
                    m.phone             AS member_phone,
                    m.email             AS member_email
                FROM pt_earnings e
                JOIN bookings b ON b.id = e.booking_id
                JOIN users m    ON m.id = b.member_id
                {$whereClause}
                ORDER BY e.recorded_at DESC, e.id DESC";

    $stmtRows = $db->prepare($sqlRows);
    $stmtRows->execute($rowParams);
    $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC);

    $formattedRows = array_map(function ($r) {
        $startDate = !empty($r['start_at']) ? new DateTime($r['start_at']) : null;
        $endDate   = !empty($r['end_at'])   ? new DateTime($r['end_at'])   : null;

        $sessionDate = $startDate ? $startDate->format('d/m/Y') : '—';
        $sessionTime = ($startDate && $endDate)
            ? $startDate->format('H:i') . ' - ' . $endDate->format('H:i')
            : ($startDate ? $startDate->format('H:i') : '—');

        $status = $r['payment_status'] ?? 'Pending';
        $statusText = ($status === 'Paid') ? 'Đã thanh toán' : 'Chờ thanh toán';

        return [
            'earning_id'          => (int) $r['earning_id'],
            'booking_id'          => (int) $r['booking_id'],
            'session_date'        => $sessionDate,
            'session_time'        => $sessionTime,
            'start_at'            => $r['start_at'],
            'end_at'              => $r['end_at'],
            'duration_minutes'    => (int) $r['duration_minutes'],
            'member_id'           => (int) $r['member_id'],
            'member_name'         => $r['member_name'], // Đảm bảo luôn là tên hội viên
            'member_phone'        => $r['member_phone'] ?: '',
            'member_email'        => $r['member_email'] ?: '',
            'sessions_count'      => 1, // Mỗi booking tương ứng 1 buổi tập
            'amount'              => (float) $r['amount'],
            'fee_snapshot'        => (float) $r['fee_snapshot'],
            'payment_status'      => $status,
            'payment_status_text' => $statusText,
            'recorded_at'         => $r['recorded_at'],
            'paid_at'             => $r['paid_at'],
            'note'                => $r['booking_note'] ?: '',
        ];
    }, $rows);

    AuthMiddleware::json(true, 'Tải thu nhập thành công.', [
        'summary' => [
            'total_earnings'   => (float) $summary['total_earnings'],
            'paid_earnings'    => (float) $summary['paid_earnings'],
            'pending_earnings' => (float) $summary['pending_earnings'],
            'total_sessions'   => (int) $summary['total_sessions'],
            'avg_per_session'  => (float) $summary['avg_per_session'],
        ],
        'chart'   => $chartData,
        'rows'    => $formattedRows,
        'filters' => [
            'month'  => $validMonth,
            'status' => $validStatus ?? 'all',
            'search' => $searchParam,
        ],
    ]);

} catch (Throwable $e) {
    AuthMiddleware::handleError($e, 'Không thể tải dữ liệu thu nhập.');
}
