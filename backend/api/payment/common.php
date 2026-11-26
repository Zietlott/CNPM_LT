<?php
declare(strict_types=1);
require_once __DIR__ . '/../../controllers/PaymentController.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function paymentResponse(int $code, array $body): never
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function paymentRequireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        paymentResponse(405, ['success' => false, 'message' => 'Method not allowed']);
    }
}
function paymentRequireMember(): void
{
    if (!isLoggedIn()) paymentResponse(401, ['success' => false, 'message' => 'Can dang nhap']);
    if (!isMember()) paymentResponse(403, ['success' => false, 'message' => 'Chi hoi vien duoc phep']);
}
function paymentReadJson(): array
{
    if (!preg_match('/^application\/json(?:\s*;|$)/i', $_SERVER['CONTENT_TYPE'] ?? '')) {
        paymentResponse(415, ['success' => false, 'message' => 'Content-Type phai la application/json']);
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || array_is_list($input)) {
        paymentResponse(400, ['success' => false, 'message' => 'JSON object khong hop le']);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!verifyCsrfToken($token)) {
        paymentResponse(403, ['success' => false, 'message' => 'CSRF token khong hop le']);
    }
    return $input;
}
function paymentPositiveInt(mixed $value): int
{
    if (!is_int($value) && !is_string($value)) paymentResponse(400, ['success' => false, 'message' => 'ID khong hop le']);
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) paymentResponse(400, ['success' => false, 'message' => 'ID khong hop le']);
    return (int)$id;
}
function paymentHandle(callable $callback, int $successCode = 200): never
{
    try {
        paymentResponse($successCode, ['success' => true, 'data' => $callback()]);
    } catch (InvalidArgumentException $e) {
        paymentResponse(404, ['success' => false, 'message' => $e->getMessage()]);
    } catch (DomainException $e) {
        paymentResponse(409, ['success' => false, 'message' => $e->getMessage()]);
    } catch (Throwable $e) {
        error_log('Payment API error: ' . $e->getMessage());
        paymentResponse(500, ['success' => false, 'message' => 'Loi xu ly thanh toan']);
    }
}
