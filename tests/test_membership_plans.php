<?php

require_once __DIR__ . '/../backend/services/MembershipService.php';

try {
    $service = new MembershipService();

    $plans = $service->getAllPlans();

    echo "TEST 1: PASS - Truy van thanh cong\n";
    echo "Tong so goi tap: " . count($plans) . "\n\n";

    foreach ($plans as $plan) {
        echo "-------------------------\n";

        foreach ($plan as $column => $value) {
            echo $column . ": " . ($value ?? 'NULL') . "\n";
        }
    }

} catch (Throwable $e) {
    echo "TEST 1: FAIL\n";
    echo "LOI: " . $e->getMessage() . "\n";
    exit(1);
}
