document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const registerForm = document.getElementById("registerForm");

    function showMessage(element, message, type) {
        if (!element) return;
        element.textContent = message;
        element.className = `form-feedback ${type}`;
    }

    if (loginForm) {
        loginForm.addEventListener("submit", (event) => {
            if (!loginForm.checkValidity()) {
                event.preventDefault();
                loginForm.reportValidity();
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (!registerForm.checkValidity()) {
                registerForm.reportValidity();
                return;
            }

            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmPassword").value;
            const feedback = document.getElementById("registerFeedback");
            const submitButton = registerForm.querySelector('button[type="submit"]');

            if (password !== confirmPassword) {
                showMessage(feedback, "Mật khẩu xác nhận không khớp.", "error");
                document.getElementById("confirmPassword").focus();
                return;
            }

            if (registerForm.dataset.submitting === "true") {
                return;
            }
            registerForm.dataset.submitting = "true";
            submitButton.disabled = true;
            showMessage(feedback, "Đang tạo tài khoản...", "info");

            try {
                const response = await fetch(window.location.href, {
                    method: "POST",
                    body: new FormData(registerForm),
                    headers: { "X-Requested-With": "XMLHttpRequest" }
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    showMessage(
                        feedback,
                        result.message || "Đăng ký thất bại. Vui lòng thử lại.",
                        "error"
                    );
                    return;
                }

                showMessage(
                    feedback,
                    "Đăng ký thành công! Đang chuyển đến trang đăng nhập...",
                    "success"
                );

                setTimeout(() => {
                    window.location.href = "login.php";
                }, 1000);
            } catch (error) {
                showMessage(
                    feedback,
                    "Không thể kết nối máy chủ. Hãy kiểm tra PHP và database.",
                    "error"
                );
            } finally {
                registerForm.dataset.submitting = "false";
                submitButton.disabled = false;
            }
        });
    }

    const forgotPassword = document.getElementById("forgotPassword");
    if (forgotPassword) {
        forgotPassword.addEventListener("click", (event) => {
            event.preventDefault();
            window.alert("Chức năng quên mật khẩu sẽ được bổ sung sau.");
        });
    }
});


