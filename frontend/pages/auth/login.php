<?php

require_once __DIR__ . '/../../../backend/controllers/AuthController.php';

$pageTitle    = "Đăng nhập";
$errorMessage = "";

/* Nếu đã đăng nhập → redirect theo role */
if (isLoggedIn()) {
    $destination = match ($_SESSION['role'] ?? 'member') {
        'admin', 'staff' => '../admin/bookings.php',
        'trainer'        => '../trainer/pt-bookings.php',
        default          => '../member/dashboard.php',
    };
    header('Location: ' . $destination);
    exit;
}

/* Xử lý POST đăng nhập */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $errorMessage = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';

    } else {
        try {
            $controller = new AuthController();
            $result     = $controller->login();

            if ($result['success']) {
                $destination = match ($result['user']['role']) {
                    'admin', 'staff' => '../admin/bookings.php',
                    'trainer'        => '../trainer/pt-bookings.php',
                    default          => '../member/dashboard.php',
                };
                header('Location: ' . $destination);
                exit;
            }

            $errorMessage = $result['message'];

        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            http_response_code(500);
            $errorMessage = 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Đăng nhập vào FitZone – Quản lý hội viên và đặt lịch PT">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | FitZone</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="../../assets/css/auth.css" rel="stylesheet">
</head>

<body class="auth-page">
<main class="container-fluid">

    <!-- Panel hình ảnh bên trái (ẩn trên mobile) -->
    <aside class="auth-visual" aria-hidden="true">
        <div class="auth-visual-content">
            <div class="auth-visual-brand">
                <span class="brand-mark">F</span> FITZONE
            </div>
            <blockquote>
                <p>"Mỗi buổi tập là một bước tiến. Hãy bắt đầu hôm nay."</p>
            </blockquote>
            <div class="auth-visual-decoration">FIT</div>
        </div>
    </aside>

    <!-- Panel form đăng nhập -->
    <section class="auth-form-side">
        <div class="auth-card">

            <!-- Mobile brand -->
            <div class="mobile-brand" aria-hidden="true">
                <span class="brand-mark">F</span> FITZONE
            </div>

            <div class="auth-heading">
                <p class="eyebrow">WELCOME BACK</p>
                <h1>Chào mừng trở lại!</h1>
                <p>Đăng nhập để tiếp tục hành trình của bạn.</p>
            </div>

            <form action="#" method="post" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        class="form-control form-control-lg<?= $errorMessage ? ' is-invalid' : '' ?>"
                        id="email"
                        name="email"
                        placeholder="name@example.com"
                        autocomplete="email"
                        required
                        aria-describedby="loginFeedback"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <!-- Mật khẩu -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <a class="small-link" href="#" id="forgotPassword" tabindex="-1">
                            Quên mật khẩu?
                        </a>
                    </div>

                    <div class="input-with-toggle">
                        <input
                            type="password"
                            class="form-control form-control-lg<?= $errorMessage ? ' is-invalid' : '' ?>"
                            id="password"
                            name="password"
                            placeholder="Nhập mật khẩu"
                            autocomplete="current-password"
                            required
                            minlength="6"
                        >
                        <button
                            type="button"
                            class="toggle-password"
                            data-target="password"
                            aria-label="Hiện/ẩn mật khẩu"
                            aria-pressed="false"
                        >Hiện</button>
                    </div>
                </div>

                <!-- Ghi nhớ -->
                <div class="form-check my-4">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Ghi nhớ đăng nhập
                    </label>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    Đăng nhập <span aria-hidden="true">→</span>
                </button>

                <!-- Feedback -->
                <p class="form-feedback <?= $errorMessage !== '' ? 'error' : '' ?>"
                   id="loginFeedback"
                   role="alert"
                   aria-live="assertive">
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </form>

            <!-- Demo accounts info -->
            <details class="demo-accounts">
                <summary>Tài khoản demo</summary>
                <ul>
                    <li><strong>Admin:</strong> admin@fitzone.vn / password123</li>
                    <li><strong>Member:</strong> member@fitzone.vn / password123</li>
                    <li><strong>Trainer:</strong> trainer@fitzone.vn / password123</li>
                </ul>
            </details>

            <p class="auth-switch">
                Chưa có tài khoản?
                <a href="register.php">Tạo tài khoản</a>
            </p>

            <p class="terms-note">
                Bằng việc tiếp tục, bạn đồng ý với các
                <a href="#" class="terms-link">điều khoản sử dụng</a> của FitZone.
            </p>

        </div>
    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="../../assets/js/auth.js"></script>
</body>
</html>
