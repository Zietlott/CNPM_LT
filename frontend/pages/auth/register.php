<?php

require_once __DIR__ . '/../../../backend/controllers/AuthController.php';

$pageTitle = "Đăng ký";

/* ---- Xử lý AJAX POST đăng ký ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $password        = $_POST['password']         ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPassword) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu xác nhận không khớp.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $controller = new AuthController();
        $result     = $controller->register();

        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Không thể tạo tài khoản. Vui lòng kiểm tra kết nối database.',
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tạo tài khoản FitZone – Bắt đầu hành trình khỏe mạnh">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | FitZone</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="../../assets/css/auth.css" rel="stylesheet">
</head>

<body class="auth-page">
<main class="container-fluid">

    <!-- Panel hình ảnh bên trái -->
    <aside class="auth-visual" aria-hidden="true">
        <div class="auth-visual-content">
            <div class="auth-visual-brand">
                <span class="brand-mark">F</span> FITZONE
            </div>
            <ul class="auth-visual-benefits">
                <li>✓ Đặt lịch PT nhanh chóng</li>
                <li>✓ Quản lý gói hội viên tiện lợi</li>
                <li>✓ Theo dõi lịch tập cá nhân</li>
                <li>✓ Kết nối với chuyên gia fitness</li>
            </ul>
            <div class="auth-visual-decoration">JOIN</div>
        </div>
    </aside>

    <!-- Panel form đăng ký -->
    <section class="auth-form-side">
        <div class="auth-card auth-card-register">

            <!-- Mobile brand -->
            <div class="mobile-brand" aria-hidden="true">
                <span class="brand-mark">F</span> FITZONE
            </div>

            <div class="auth-heading">
                <p class="eyebrow">JOIN FITZONE</p>
                <h1>Tạo tài khoản</h1>
                <p>Điền thông tin bên dưới để bắt đầu hành trình.</p>
            </div>

            <form action="#" method="post" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                <!-- Họ tên -->
                <div class="mb-3">
                    <label for="fullName" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control"
                        id="fullName"
                        name="full_name"
                        placeholder="Nguyễn Văn An"
                        autocomplete="name"
                        required
                        minlength="2"
                        maxlength="100"
                    >
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        placeholder="name@example.com"
                        autocomplete="email"
                        required
                        maxlength="150"
                    >
                </div>

                <!-- Số điện thoại -->
                <div class="mb-3">
                    <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                    <input
                        type="tel"
                        class="form-control"
                        id="phone"
                        name="phone"
                        placeholder="Ví dụ: 0901234567"
                        autocomplete="tel"
                        pattern="[0-9+() .\-]{9,15}"
                        required
                        maxlength="20"
                    >
                </div>

                <!-- Mật khẩu + Xác nhận -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <div class="input-with-toggle">
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Ít nhất 8 ký tự"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="128"
                                required
                            >
                            <button
                                type="button"
                                class="toggle-password"
                                data-target="password"
                                aria-label="Hiện/ẩn mật khẩu"
                                aria-pressed="false"
                            >Hiện</button>
                        </div>
                        <!-- Password strength bar -->
                        <div class="password-strength" aria-hidden="true">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <p class="password-hint">Nên dùng chữ hoa, số, ký tự đặc biệt.</p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="confirmPassword" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <div class="input-with-toggle">
                            <input
                                type="password"
                                class="form-control"
                                id="confirmPassword"
                                name="confirm_password"
                                placeholder="Nhập lại mật khẩu"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="128"
                                required
                            >
                            <button
                                type="button"
                                class="toggle-password"
                                data-target="confirmPassword"
                                aria-label="Hiện/ẩn xác nhận mật khẩu"
                                aria-pressed="false"
                            >Hiện</button>
                        </div>
                    </div>
                </div>

                <!-- Đồng ý điều khoản -->
                <div class="form-check mb-4">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="agree"
                        name="agree"
                        required
                    >
                    <label class="form-check-label" for="agree">
                        Tôi đồng ý với
                        <a href="#" class="terms-link">điều khoản sử dụng</a>
                        và
                        <a href="#" class="terms-link">chính sách quyền riêng tư</a>.
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    Tạo tài khoản <span aria-hidden="true">→</span>
                </button>

                <!-- Feedback -->
                <p
                    class="form-feedback"
                    id="registerFeedback"
                    role="status"
                    aria-live="polite"
                ></p>

            </form>

            <p class="auth-switch">
                Đã có tài khoản?
                <a href="login.php">Đăng nhập</a>
            </p>

            <p class="terms-note">
                Thông tin của bạn sẽ được sử dụng theo chính sách quyền riêng tư của FitZone.
            </p>

        </div>
    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="../../assets/js/auth.js"></script>
</body>
</html>
