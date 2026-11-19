<?php
/**
 * HTML <head> SHARED COMPONENT
 * FitZone - Fitness Center Membership & PT Booking App
 *
 * Biến đầu vào (định nghĩa trước khi include):
 *   $pageTitle    - tiêu đề tab trình duyệt
 *   $extraCss     - mảng các đường dẫn CSS bổ sung (tương đối từ trang gọi)
 *                   Ví dụ: ['../../assets/css/booking.css']
 */

$pageTitle = $pageTitle ?? 'FitZone';
$extraCss  = $extraCss  ?? [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FitZone – Quản lý hội viên và đặt lịch PT">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | FitZone</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        crossorigin="anonymous"
    >

    <!-- Google Fonts (preconnect for performance) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Base dashboard stylesheet -->
    <link href="../../assets/css/dashboard.css" rel="stylesheet">

    <!-- Page-specific stylesheets -->
    <?php foreach ($extraCss as $cssPath): ?>
        <link href="<?= htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>
