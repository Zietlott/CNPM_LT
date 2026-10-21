// Xử lý tương tác giao diện mẫu. Chưa gửi dữ liệu đến backend.
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
        registerForm.addEventListener("submit", (event) => {
            event.preventDefault();
            if (!registerForm.checkValidity()) {
                registerForm.reportValidity();
                return;
            }

            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmPassword").value;

            if (password !== confirmPassword) {
                showMessage(
                    document.getElementById("registerFeedback"),
                    "Mật khẩu xác nhận chưa khớp. Vui lòng kiểm tra lại.",
                    "error"
                );
                document.getElementById("confirmPassword").focus();
                return;
            }

            showMessage(
                document.getElementById("registerFeedback"),
                "Tạo tài khoản thành công! Đang chuyển hướng đến trang đăng nhập...",
                "success"
            );
            setTimeout(() => {
                window.location.href = "login.php";
            }, 1000);
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
