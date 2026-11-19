<?php
/**
 * HTML FOOTER + SCRIPTS SHARED COMPONENT
 * FitZone - Fitness Center Membership & PT Booking App
 *
 * Biến đầu vào (định nghĩa trước khi include):
 *   $extraJs  - mảng các đường dẫn JS bổ sung (tương đối từ trang gọi)
 *               Ví dụ: ['../../assets/js/booking.js']
 *
 * Luôn load: Bootstrap bundle + dashboard.js (sidebar toggle)
 */

$extraJs = $extraJs ?? [];
?>

    <footer class="dashboard-footer">
        &copy; <?= date('Y') ?> FitZone. All rights reserved.
    </footer>

</main><!-- /.dashboard-content -->
</div><!-- /.main-area -->
</div><!-- /.dashboard-layout -->

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Bootstrap JS -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"
></script>

<!-- Shared dashboard JS (sidebar toggle, resize handler) -->
<script src="../../assets/js/dashboard.js"></script>

<!-- Page-specific scripts -->
<?php foreach ($extraJs as $jsPath): ?>
    <script src="<?= htmlspecialchars($jsPath, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>

</body>
</html>
