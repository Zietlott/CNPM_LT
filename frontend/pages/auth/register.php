
<?php
// Trang đăng ký giao diện mẫu.
// Chưa lưu tài khoản vào database; sẽ tích hợp API sau.
$pageTitle = "Đăng ký";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?> | FitZone</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link href="../../assets/css/auth.css" rel="stylesheet">
</head>

<body class="auth-page">
    <main class="container-fluid">
        <section class="auth-form-side">
            <div class="auth-card auth-card-register">

                <div class="auth-heading">
                    <p class="eyebrow">JOIN FITZONE</p>
                    <h2>Tạo tài khoản</h2>
                    <p>Điền thông tin bên dưới để bắt đầu.</p>
                </div>

                <form action="#" method="post" id="registerForm">

                    <div class="mb-3">
                        <label for="fullName" class="form-label">
                            Họ và tên
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="fullName"
                            name="full_name"
                            placeholder="Nguyễn Văn An"
                            autocomplete="name"
                            required
                            minlength="2"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            placeholder="name@example.com"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            Số điện thoại
                        </label>
                        <input
                            type="tel"
                            class="form-control"
                            id="phone"
                            name="phone"
                            placeholder="Ví dụ: 0901234567"
                            autocomplete="tel"
                            pattern="[0-9+() .-]{9,15}"
                            required
                        >
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                Mật khẩu
                            </label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Ít nhất 8 ký tự"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="confirmPassword" class="form-label">
                                Xác nhận mật khẩu
                            </label>
                            <input
                                type="password"
                                class="form-control"
                                id="confirmPassword"
                                name="confirm_password"
                                placeholder="Nhập lại mật khẩu"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="agree"
                            name="agree"
                            required
                        >
                        <label class="form-check-label" for="agree">
                            Tôi đồng ý với điều khoản sử dụng.
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary btn-lg w-100"
                    >
                        Tạo tài khoản
                        <span aria-hidden="true">→</span>
                    </button>

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
                    Thông tin của bạn sẽ được sử dụng theo chính sách
                    quyền riêng tư của FitZone.
                </p>

            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/auth.js"></script>
</body>
</html>
