<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../services/TrainerService.php';

try {
    $service = new TrainerService();
    $trainers = $service->getAllTrainers();

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Lấy danh sách PT thành công',
        'data' => $trainers
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Không thể lấy danh sách PT'
    ], JSON_UNESCAPED_UNICODE);
}
