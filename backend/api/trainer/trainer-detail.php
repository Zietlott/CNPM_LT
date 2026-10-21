s<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../services/TrainerService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');

    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$trainerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($trainerId === false || $trainerId === null || $trainerId <= 0) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'ID huấn luyện viên không hợp lệ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $service = new TrainerService();
    $trainer = $service->getTrainerById($trainerId);

    if ($trainer === null) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy huấn luyện viên'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Lấy thông tin PT thành công',
        'data' => $trainer
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Không thể lấy thông tin PT'
    ], JSON_UNESCAPED_UNICODE);
}
