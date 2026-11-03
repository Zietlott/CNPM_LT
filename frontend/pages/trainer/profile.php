<?php
require_once __DIR__ . '/../../../backend/helpers/auth.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - FitZone</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f5f8;
            color: #202938;
        }

        .container {
            max-width: 680px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 6px 25px rgba(0,0,0,.06);
        }

        h1 { margin-top: 0; }

        .subtitle {
            color: #687385;
            margin-bottom: 28px;
        }

        label {
            display: block;
            font-weight: bold;
            margin: 18px 0 8px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d6dce5;
            border-radius: 8px;
            font-size: 15px;
        }

        input:disabled {
            background: #f1f3f5;
            color: #687385;
        }

        button {
            background: #2563eb;
            color: white;
            border: 0;
            border-radius: 8px;
            padding: 13px 20px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 24px;
        }

        button:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
        }

        #message { margin-top: 18px; }

        .success { color: #15803d; }
        .error { color: #dc2626; }
    </style>
</head>
<body>

<div class="container">
    <a class="back" href="../member/dashboard.php">
        ← Quay lại Dashboard
    </a>

    <div class="card">
        <h1>Thông tin cá nhân</h1>
        <p class="subtitle">
            Xem và cập nhật thông tin tài khoản FitZone.
        </p>

        <form id="profileForm">
            <label for="full_name">Họ và tên</label>
            <input id="full_name" type="text"
                   maxlength="100" required>

            <label for="email">Email</label>
            <input id="email" type="email" disabled>

            <label for="phone">Số điện thoại</label>
            <input id="phone" type="tel"
                   maxlength="20" required>

            <label for="role">Vai trò</label>
            <input id="role" type="text" disabled>

            <label for="created_at">Ngày tạo tài khoản</label>
            <input id="created_at" type="text" disabled>

            <button id="saveBtn" type="submit">
                Lưu thay đổi
            </button>
        </form>

        <p id="message" role="status" aria-live="polite"></p>
    </div>
</div>

<script>
const API_BASE = '/CNPM_LT/backend/api/profile';
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;

const form = document.getElementById('profileForm');
const message = document.getElementById('message');
const saveBtn = document.getElementById('saveBtn');

function showMessage(text, success = false) {
    message.textContent = text;
    message.className = success ? 'success' : 'error';
}

async function loadProfile() {
    try {
        const response = await fetch(API_BASE + '/me.php', {
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Không tải được thông tin.');
        }

        const profile = result.data;

        document.getElementById('full_name').value = profile.full_name;
        document.getElementById('email').value = profile.email;
        document.getElementById('phone').value = profile.phone;
        document.getElementById('role').value = profile.role;
        document.getElementById('created_at').value = profile.created_at;

    } catch (error) {
        showMessage(error.message);
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    saveBtn.disabled = true;
    showMessage('');

    try {
        const response = await fetch(API_BASE + '/update.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify({
                full_name: document.getElementById('full_name').value,
                phone: document.getElementById('phone').value
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Cập nhật thất bại.');
        }

        showMessage(result.message, true);
        await loadProfile();

    } catch (error) {
        showMessage(error.message);
    } finally {
        saveBtn.disabled = false;
    }
});

loadProfile();
</script>

</body>
</html>
