<?php
require_once __DIR__ . '/common.php';
paymentRequireMethod('POST');
paymentRequireMember();
$input = paymentReadJson();
$membershipId = paymentPositiveInt($input['membership_id'] ?? null);
paymentHandle(static fn() => (new PaymentController())->create($membershipId), 200);
