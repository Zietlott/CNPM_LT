/**
 * FITZONE - AUTH JS (auth.js)
 * Xử lý form đăng nhập và đăng ký với UX cải thiện.
 * Dùng FitzoneUI nếu có, fallback sang helper nội bộ.
 */

"use strict";

document.addEventListener("DOMContentLoaded", () => {

    /* ---- Helpers ---- */
    function showMsg(el, msg, type) {
        if (!el) return;
        el.textContent = msg;
        el.className   = `form-feedback ${type}`;
    }

    function setLoading(btn, isLoading, defaultText = "Gửi") {
        btn.disabled   = isLoading;
        btn.textContent = isLoading ? "Đang xử lý..." : defaultText;
    }

    function highlightInput(id, hasError = true) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle("is-invalid", hasError);
        el.classList.toggle("is-valid",   !hasError);
    }

    /* ============================================================
       FORM ĐĂNG NHẬP
       ============================================================ */
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", (e) => {
            if (!loginForm.checkValidity()) {
                e.preventDefault();
                loginForm.reportValidity();
            }
            // Nếu hợp lệ → submit bình thường (server-side render)
        });

        // Real-time validation email
        const emailInput = loginForm.querySelector('[name="email"]');
        if (emailInput) {
            emailInput.addEventListener("blur", () => {
                highlightInput("email", !emailInput.validity.valid);
            });
        }
    }

    /* ============================================================
       FORM ĐĂNG KÝ (AJAX)
       ============================================================ */
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
        const feedback      = document.getElementById("registerFeedback");
        const submitBtn     = registerForm.querySelector('button[type="submit"]');
        const passwordInput = document.getElementById("password");
        const confirmInput  = document.getElementById("confirmPassword");
        const DEFAULT_TEXT  = submitBtn?.textContent || "Tạo tài khoản";

        /* Password strength indicator */
        if (passwordInput) {
            const bar = document.getElementById("passwordStrengthBar");
            passwordInput.addEventListener("input", () => {
                if (!bar) return;
                const v = passwordInput.value;
                let strength = 0;
                if (v.length >= 8)                   strength++;
                if (/[A-Z]/.test(v))                 strength++;
                if (/[0-9]/.test(v))                 strength++;
                if (/[^A-Za-z0-9]/.test(v))          strength++;
                bar.className = `password-strength-bar strength-${strength}`;
            });
        }

        /* Realtime confirm password check */
        if (confirmInput && passwordInput) {
            confirmInput.addEventListener("input", () => {
                const match = confirmInput.value === passwordInput.value;
                highlightInput("confirmPassword", !match);
            });
        }

        /* Submit */
        registerForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            if (!registerForm.checkValidity()) {
                registerForm.reportValidity();
                return;
            }

            const pwd     = passwordInput?.value || "";
            const confirm = confirmInput?.value  || "";

            if (pwd !== confirm) {
                showMsg(feedback, "Mật khẩu xác nhận không khớp.", "error");
                confirmInput?.focus();
                return;
            }

            if (registerForm.dataset.submitting === "true") return;
            registerForm.dataset.submitting = "true";
            setLoading(submitBtn, true, DEFAULT_TEXT);
            showMsg(feedback, "Đang tạo tài khoản...", "info");

            try {
                const response = await fetch(window.location.href, {
                    method:  "POST",
                    body:    new FormData(registerForm),
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    showMsg(feedback, result.message || "Đăng ký thất bại. Vui lòng thử lại.", "error");
                    return;
                }

                showMsg(feedback, "Đăng ký thành công! Đang chuyển đến trang đăng nhập...", "success");

                setTimeout(() => { window.location.href = "login.php"; }, 1200);

            } catch (_) {
                showMsg(feedback, "Không thể kết nối máy chủ. Hãy kiểm tra PHP và database.", "error");
            } finally {
                registerForm.dataset.submitting = "false";
                setLoading(submitBtn, false, DEFAULT_TEXT);
            }
        });
    }

    /* ============================================================
       QUÊN MẬT KHẨU (placeholder)
       ============================================================ */
    const forgotBtn = document.getElementById("forgotPassword");
    if (forgotBtn) {
        forgotBtn.addEventListener("click", (e) => {
            e.preventDefault();
            window.alert("Chức năng quên mật khẩu sẽ được bổ sung sau.\nHãy liên hệ quản trị viên FitZone.");
        });
    }

    /* ============================================================
       TOGGLE HIỂN THỊ MẬT KHẨU
       ============================================================ */
    document.querySelectorAll(".toggle-password").forEach((btn) => {
        btn.addEventListener("click", () => {
            const targetId = btn.dataset.target;
            const input    = document.getElementById(targetId);
            if (!input) return;
            const isPassword = input.type === "password";
            input.type       = isPassword ? "text" : "password";
            btn.textContent  = isPassword ? "Ẩn" : "Hiện";
            btn.setAttribute("aria-pressed", String(isPassword));
        });
    });
});
