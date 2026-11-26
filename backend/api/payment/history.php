<?php
require_once __DIR__ . '/common.php';
paymentRequireMethod('GET');
paymentRequireMember();
paymentHandle(static fn() => (new PaymentController())->history());
