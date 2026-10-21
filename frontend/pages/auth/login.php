<?php

require_once __DIR__ . '/../../../backend/controllers/AuthController.php';

$pageTitle = "Đăng nhập";
$errorMessage = "";

// Nếu đã đăng nhập thì chuyển đến Dashboard
if (isLoggedIn()) {
    header("Location: ../member/dashboard.php");
    exit;
}

// Xử lý khi người dùng gửi form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    $result = $controller->login();

    if ($result['success']) {
        header("Location: ../member/dashboard.php");
        exit;
    }

    $errorMessage = $result['message'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | FitZone</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/auth.css" rel="stylesheet">


</head>

<body class="auth-page">
    <main class="container-fluid">
        <section class="auth-form-side">
            <div class="auth-card">

                <div class="auth-heading">
                    <p class="eyebrow">WELCOME BACK</p>
                    <h2>Chào mừng trở lại!</h2>
                    <p>Đăng nhập để tiếp tục hành trình của bạn.</p>
                </div>

                <form action="#" method="post" id="loginForm">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            class="form-control form-control-lg"
                            id="email"
                            name="email"
                            placeholder="name@example.com"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <a class="small-link" href="#" id="forgotPassword">
                                Quên mật khẩu?
                            </a>
                        </div>

                        <input
                            type="password"
                            class="form-control form-control-lg"
                            id="password"
                            name="password"
                            placeholder="Nhập mật khẩu"
                            autocomplete="current-password"
                            required
                            minlength="6"
                        >
                    </div>

                    <div class="form-check my-4">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="remember"
                            name="remember"
                        >
                        <label class="form-check-label" for="remember">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        Đăng nhập <span aria-hidden="true">→</span>
                    </button>

                    <p class="form-feedback <?= $errorMessage !== '' ? 'error' : '' ?>"
                    id="loginFeedback"
                    role="status"
                    aria-live="polite">
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </form>

                <p class="auth-switch">
                    Chưa có tài khoản?
                    <a href="register.php">Tạo tài khoản</a>
                </p>

                <p class="terms-note">
                    Bằng việc tiếp tục, bạn đồng ý với các điều khoản sử dụng của FitZone.
                </p>

            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/auth.js"></script>
</body>
</html>
