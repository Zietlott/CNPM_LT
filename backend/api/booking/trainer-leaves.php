<?php
/**
 * API QUẢN LÝ THỜI GIAN NGHỈ / KHÓA LỊCH CỦA HUẤN LUYỆN VIÊN (PT)
 * FITZONE - FITNESS CENTER
 */

require_once __DIR__ . '/common.php';

bookingRequireRole('trainer');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = getDBConnection();
$memberId = (int) ($_SESSION['member_id'] ?? 0);

// Lấy trainer_id của PT
$stmtTrainer = $db->prepare('SELECT trainer_id FROM personal_trainer WHERE member_id = :member_id LIMIT 1');
$stmtTrainer->execute(['member_id' => $memberId]);
$trainerId = (int) $stmtTrainer->fetchColumn();

if ($trainerId <= 0) {
    bookingRespond([
        'success' => false,
        'message' => 'Không tìm thấy hồ sơ huấn luyện viên tương ứng.',
        'errorCode' => 'TRAINER_NOT_FOUND'
    ], 404);
}

// ----------------------------------------------------
// 1. GET: Danh sách các ngày/khung giờ nghỉ đã đăng ký
// ----------------------------------------------------
if ($method === 'GET') {
    try {
        $stmt = $db->prepare(
            'SELECT id, pt_id, leave_date, start_time, end_time, reason, created_at
             FROM trainer_leaves
             WHERE pt_id = :trainer_id
             ORDER BY leave_date DESC, id DESC'
        );
        $stmt->execute(['trainer_id' => $trainerId]);
        $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        bookingRespond([
            'success' => true,
            'data' => $leaves
        ]);
    } catch (Throwable $e) {
        bookingHandleError($e);
    }
}

// Yêu cầu CSRF cho các thao tác ghi
bookingRequireCsrf();
$input = bookingReadJsonInput();

// ----------------------------------------------------
// 2. DELETE: Hủy ngày nghỉ đã đăng ký
// ----------------------------------------------------
if ($method === 'DELETE') {
    $leaveId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($leaveId === false) {
        bookingRespond([
            'success' => false,
            'message' => 'Mã ngày nghỉ không hợp lệ.',
            'errorCode' => 'INVALID_LEAVE_DATA'
        ], 422);
    }

    try {
        $stmt = $db->prepare('DELETE FROM trainer_leaves WHERE id = :id AND pt_id = :trainer_id');
        $stmt->execute(['id' => $leaveId, 'trainer_id' => $trainerId]);

        if ($stmt->rowCount() === 0) {
            bookingRespond([
                'success' => false,
                'message' => 'Không tìm thấy ngày nghỉ hoặc bạn không có quyền xóa.',
                'errorCode' => 'LEAVE_NOT_FOUND'
            ], 404);
        }

        bookingRespond([
            'success' => true,
            'message' => 'Đã hủy đăng ký nghỉ thành công.'
        ]);
    } catch (Throwable $e) {
        bookingHandleError($e);
    }
}

// ----------------------------------------------------
// 3. POST: Đăng ký ngày nghỉ / khung giờ nghỉ mới
// ----------------------------------------------------
if ($method === 'POST') {
    $leaveDate = trim((string) ($input['leave_date'] ?? ''));
    $startTime = !empty($input['start_time']) ? trim((string) $input['start_time']) : null;
    $endTime   = !empty($input['end_time']) ? trim((string) $input['end_time']) : null;
    $reason    = trim((string) ($input['reason'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $leaveDate)) {
        bookingRespond([
            'success' => false,
            'message' => 'Ngày nghỉ không hợp lệ (định dạng YYYY-MM-DD).',
            'errorCode' => 'INVALID_DATE'
        ], 422);
    }

    if ($reason === '') {
        bookingRespond([
            'success' => false,
            'message' => 'Vui lòng nhập lý do nghỉ.',
            'errorCode' => 'MISSING_REASON'
        ], 422);
    }

    // Không cho phép đăng ký ngày nghỉ trong quá khứ
    $today = date('Y-m-d');
    if ($leaveDate < $today) {
        bookingRespond([
            'success' => false,
            'message' => 'Không thể đăng ký nghỉ cho ngày trong quá khứ.',
            'errorCode' => 'LEAVE_IN_PAST'
        ], 422);
    }

    try {
        // KIỂM TRA XUNG ĐỘT VỚI BOOKING ĐÃ CÓ CỦA HỘI VIÊN (Pending hoặc Confirmed)
        $conflictSql = "
            SELECT b.id AS booking_id, b.status, u.full_name AS member_name, u.phone AS member_phone,
                   TIME(s.start_at + INTERVAL 7 HOUR) AS start_time,
                   TIME(s.end_at + INTERVAL 7 HOUR) AS end_time
            FROM bookings b
            JOIN schedules s ON s.id = b.schedule_id
            JOIN users u ON u.id = b.member_id
            WHERE s.pt_id = :trainer_id
              AND DATE(s.start_at + INTERVAL 7 HOUR) = :leave_date
              AND b.status IN ('Pending', 'Confirmed')
        ";
        $conflictParams = [
            'trainer_id' => $trainerId,
            'leave_date' => $leaveDate
        ];

        if ($startTime && $endTime) {
            $conflictSql .= " AND TIME(s.start_at + INTERVAL 7 HOUR) < :end_time
                              AND TIME(s.end_at + INTERVAL 7 HOUR) > :start_time";
            $conflictParams['start_time'] = $startTime;
            $conflictParams['end_time']   = $endTime;
        }

        $stmtConflict = $db->prepare($conflictSql);
        $stmtConflict->execute($conflictParams);
        $conflicts = $stmtConflict->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($conflicts)) {
            $conflictCount = count($conflicts);
            $names = array_map(function($c) {
                return $c['member_name'] . ' (' . substr($c['start_time'], 0, 5) . ' - ' . substr($c['end_time'], 0, 5) . ', ' . $c['status'] . ')';
            }, $conflicts);

            bookingRespond([
                'success' => false,
                'message' => "Không thể đăng ký nghỉ! Ngày {$leaveDate} đã có {$conflictCount} lịch tập với hội viên: " . implode('; ', $names) . ". Vui lòng liên hệ hội viên đổi lịch hoặc hủy booking trước.",
                'errorCode' => 'BOOKING_CONFLICT',
                'conflicts' => $conflicts
            ], 409);
        }

        // Nếu không có xung đột, khóa các slot Open trong ngày nghỉ này
        $db->beginTransaction();

        $lockSlotSql = "
            UPDATE schedules
            SET status = 'Closed'
            WHERE pt_id = :trainer_id
              AND DATE(start_at + INTERVAL 7 HOUR) = :leave_date
              AND status = 'Open'
        ";
        $lockParams = [
            'trainer_id' => $trainerId,
            'leave_date' => $leaveDate
        ];
        if ($startTime && $endTime) {
            $lockSlotSql .= " AND TIME(start_at + INTERVAL 7 HOUR) < :end_time
                              AND TIME(end_at + INTERVAL 7 HOUR) > :start_time";
            $lockParams['start_time'] = $startTime;
            $lockParams['end_time']   = $endTime;
        }
        $stmtLock = $db->prepare($lockSlotSql);
        $stmtLock->execute($lockParams);

        // Thêm vào bảng trainer_leaves
        $stmtInsert = $db->prepare(
            'INSERT INTO trainer_leaves (pt_id, leave_date, start_time, end_time, reason, created_at)
             VALUES (:pt_id, :leave_date, :start_time, :end_time, :reason, NOW())'
        );
        $stmtInsert->execute([
            'pt_id'      => $trainerId,
            'leave_date' => $leaveDate,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'reason'     => $reason
        ]);
        $leaveId = (int) $db->lastInsertId();

        $db->commit();

        bookingRespond([
            'success' => true,
            'message' => "Đã đăng ký nghỉ ngày {$leaveDate} thành công.",
            'data' => [
                'id'         => $leaveId,
                'leave_date' => $leaveDate,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'reason'     => $reason
            ]
        ], 201);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        bookingHandleError($e);
    }
}

bookingRespond(['success' => false, 'message' => 'Phương thức không được hỗ trợ.'], 405);
