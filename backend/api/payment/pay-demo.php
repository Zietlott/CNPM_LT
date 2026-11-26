<?php
require_once __DIR__ . '/common.php';
paymentRequireMethod('POST');
// Fail closed: both conditions are required, and neither should be enabled in production.
if (getenv('APP_ENV') !== 'local' || getenv('PAYMENT_DEMO_ENABLED') !== '1') {
    paymentResponse(404, ['success' => false, 'message' => 'Demo payment disabled']);
}
paymentRequireMember();
$input = paymentReadJson();
$paymentId = paymentPositiveInt($input['payment_id'] ?? null);
if (!isset($input['succeed']) || !is_bool($input['succeed'])) {
    paymentResponse(400, ['success' => false, 'message' => 'succeed phai la boolean']);
}
paymentHandle(static fn() => (new PaymentController())->payDemo($paymentId, $input['succeed']));
