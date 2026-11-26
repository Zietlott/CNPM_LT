<?php

require_once __DIR__ . '/../../../backend/helpers/auth.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isMember()) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Gói hội viên';
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | FitZone</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/dashboard.css" rel="stylesheet">
    <link href="../../assets/css/membership.css" rel="stylesheet">

    <style>
        .payment-panel {
            display: none;
            margin-top: 24px;
            padding: 24px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }
        .payment-panel.visible { display: block; }
        .payment-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }
        .payment-actions button {
            padding: 11px 18px;
            border-radius: 8px;
            border: 1px solid #16a34a;
            background: #16a34a;
            color: white;
            font-weight: 700;
        }
        .payment-actions button.secondary {
            background: white;
            color: #dc2626;
            border-color: #dc2626;
        }
        .payment-actions button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }
        .payment-feedback {
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            display: none;
        }
        .payment-feedback.visible { display: block; }
        .payment-feedback.success { background: #dcfce7; color: #166534; }
        .payment-feedback.error { background: #fee2e2; color: #991b1b; }
        .payment-feedback.info { background: #eff6ff; color: #1d4ed8; }
        .history-table-wrap { overflow-x: auto; }
        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th, .history-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            white-space: nowrap;
        }
        .history-table th { background: #f9fafb; }
        .membership-button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }
        @media (max-width: 900px) {
            .membership-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 600px) {
            .membership-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body class="dashboard-page">

<input type="hidden" id="pageCsrfToken"
       value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

<div class="dashboard-layout">

    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="brand">
            <span class="brand-mark">F</span>
            <span>FITZONE</span>
        </a>

        <p class="menu-label">MENU CHÍNH</p>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">⌂</span>
                <span>Trang chủ</span>
            </a>
            <a href="membership.php" class="nav-item active">
                <span class="nav-icon">▣</span>
                <span>Gói hội viên</span>
            </a>
            <a href="../trainer/trainer.php" class="nav-item">
                <span class="nav-icon">♙</span>
                <span>Huấn luyện viên</span>
            </a>
            <a href="../trainer/booking.php" class="nav-item">
                <span class="nav-icon">▦</span>
                <span>Đặt lịch tập</span>
            </a>
            <a href="../trainer/schedule.php" class="nav-item">
                <span class="nav-icon">◷</span>
                <span>Lịch PT</span>
            </a>
            <a href="../trainer/profile.php" class="nav-item">
                <span class="nav-icon">⚙</span>
                <span>Thông tin cá nhân</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../auth/logout.php" class="nav-item logout-link">
                <span class="nav-icon">↪</span>
                <span>Đăng xuất</span>
            </a>
        </div>
    </aside>

    <div class="main-area">

        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle"
                    aria-label="Mở menu" aria-controls="sidebar"
                    aria-expanded="false">☰</button>

            <div class="topbar-title">
                <span class="topbar-label">MEMBER AREA</span>
                <h1>Gói hội viên</h1>
            </div>

            <div class="user-info">
                <div class="user-avatar">HV</div>
                <div class="user-text">
                    <strong>Hội viên</strong>
                    <span>Chào mừng bạn!</span>
                </div>
            </div>
        </header>

        <main class="dashboard-content">

            <section class="page-intro">
                <p class="banner-label">CHOOSE YOUR MEMBERSHIP</p>
                <h2>Chọn gói tập phù hợp</h2>
                <p>
                    Các gói tập được cập nhật trực tiếp từ hệ thống FitZone.
                    Chọn gói phù hợp và thực hiện thanh toán để kích hoạt.
                </p>
            </section>

            <section class="section-block">
                <h2>Gói tập hiện có</h2>
                <div id="membershipGrid" class="membership-grid">
                    <p>Đang tải danh sách gói tập...</p>
                </div>
                <div id="planFeedback" class="payment-feedback"
                     role="status" aria-live="polite"></div>
            </section>

            <section id="currentMembershipPanel" class="payment-panel">
                <h2>Gói hội viên hiện tại</h2>
                <div id="currentMembership"></div>
            </section>

            <section id="paymentPanel" class="payment-panel">
                <h2>Thanh toán gói tập</h2>
                <div id="paymentDetails"></div>

                <p style="margin-top:14px;color:#6b7280;font-size:13px">
                    Đây là phương thức thanh toán mô phỏng phục vụ kiểm thử.
                    Không sử dụng tiền thật.
                </p>

                <div class="payment-actions">
                    <button type="button" id="paySuccessBtn">
                        Mô phỏng thanh toán thành công
                    </button>
                    <button type="button" id="payFailBtn" class="secondary">
                        Mô phỏng thanh toán thất bại
                    </button>
                </div>

                <div id="paymentFeedback" class="payment-feedback"
                     role="status" aria-live="polite"></div>
            </section>

            <section class="section-block" style="margin-top:30px">
                <h2>Lịch sử thanh toán</h2>
                <div class="history-table-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Mã giao dịch</th>
                                <th>Membership</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody id="paymentHistory">
                            <tr><td colspan="5">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <footer class="dashboard-footer">
                © <?= date('Y') ?> FitZone. All rights reserved.
            </footer>

        </main>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/dashboard.js"></script>

<script>
"use strict";

document.addEventListener("DOMContentLoaded", async () => {
    const api = new FitzoneAPI(
        "../../../backend/api/",
        document.getElementById("pageCsrfToken").value
    );

    const grid = document.getElementById("membershipGrid");
    const planFeedback = document.getElementById("planFeedback");
    const paymentPanel = document.getElementById("paymentPanel");
    const paymentDetails = document.getElementById("paymentDetails");
    const paymentFeedback = document.getElementById("paymentFeedback");
    const historyBody = document.getElementById("paymentHistory");
    const currentPanel = document.getElementById("currentMembershipPanel");
    const currentContent = document.getElementById("currentMembership");

    let pendingPayment = null;
    let processing = false;

    const escape = FitzoneUI.escape.bind(FitzoneUI);
    const currency = FitzoneUI.formatCurrency.bind(FitzoneUI);

    function feedback(element, message, type) {
        element.textContent = message;
        element.className = "payment-feedback visible " + type;
    }

    function hideFeedback(element) {
        element.className = "payment-feedback";
        element.textContent = "";
    }

    function setBusy(busy) {
        processing = busy;
        document.querySelectorAll(".membership-button, .payment-actions button")
            .forEach(button => button.disabled = busy);
    }

    function statusLabel(status) {
        const labels = {
            Pending: "Đang chờ thanh toán",
            Success: "Thành công",
            Failed: "Thất bại",
            PendingPayment: "Chờ thanh toán",
            Active: "Đang hoạt động",
            Expired: "Đã hết hạn",
            Cancelled: "Đã hủy"
        };
        return labels[status] || status || "Không xác định";
    }

    async function loadCurrentMembership() {
        try {
            const result = await api.get("membership/current.php");
            const membership = result?.membership_id
                ? result
                : result?.data?.membership_id ? result.data : null;

            if (!membership) {
                currentPanel.classList.remove("visible");
                return false;
            }

            currentContent.innerHTML = `
                <h3>${escape(membership.plan_name)}</h3>
                <p>Trạng thái: <strong>${escape(statusLabel(membership.status))}</strong></p>
                <p>Ngày bắt đầu: ${escape(membership.start_date || "Chưa kích hoạt")}</p>
                <p>Ngày kết thúc: ${escape(membership.end_date || "Chưa xác định")}</p>
            `;

            currentPanel.classList.add("visible");
            return true;
        } catch (error) {
            console.error("Load current membership:", error);
            return false;
        }
    }

    async function loadPlans() {
        try {
            const plans = await api.get("membership/plans.php");

            if (!Array.isArray(plans)) {
                throw new Error("Dữ liệu gói tập không hợp lệ");
            }

            if (plans.length === 0) {
                grid.innerHTML = "<p>Hiện chưa có gói tập được mở bán.</p>";
                return;
            }

            grid.innerHTML = plans.map((plan, index) => `
                <article class="membership-card">
                    ${index === 1
                        ? '<span class="popular-label">ĐƯỢC QUAN TÂM</span>'
                        : ''
                    }
                    <div class="membership-card-heading">
                        <h3>${escape(plan.name)}</h3>
                        <p>${escape(plan.description || "Gói tập FitZone")}</p>
                    </div>
                    <div class="membership-price">
                        <strong>${currency(plan.price)}</strong>
                        <p>${escape(plan.duration_days)} ngày</p>
                    </div>
                    <div class="membership-divider"></div>
                    <p class="features-title">Thông tin gói tập</p>
                    <ul class="membership-features">
                        <li><span class="feature-check">✓</span>
                            Thời hạn ${escape(plan.duration_days)} ngày
                        </li>
                        <li><span class="feature-check">✓</span>
                            Được sử dụng dịch vụ FitZone
                        </li>
                        <li><span class="feature-check">✓</span>
                            Có thể đặt lịch với PT
                        </li>
                    </ul>
                    <button type="button"
                        class="membership-button"
                        data-plan-id="${Number(plan.id)}">
                        Đăng ký gói này <span>→</span>
                    </button>
                </article>
            `).join("");

        } catch (error) {
            grid.innerHTML = `<p>Không thể tải gói tập: ${escape(error.message)}</p>`;
        }
    }

    async function loadPaymentHistory() {
        try {
            const history = await api.get("payment/history.php");

            if (!Array.isArray(history) || history.length === 0) {
                historyBody.innerHTML =
                    '<tr><td colspan="5">Chưa có giao dịch thanh toán.</td></tr>';
                return;
            }

            historyBody.innerHTML = history.map(item => `
                <tr>
                    <td>${escape(item.id)}</td>
                    <td>${escape(item.membership_id || "—")}</td>
                    <td>${currency(item.amount)}</td>
                    <td>${escape(statusLabel(item.status))}</td>
                    <td>${escape(item.paid_at || item.created_at || "—")}</td>
                </tr>
            `).join("");

        } catch (error) {
            historyBody.innerHTML =
                `<tr><td colspan="5">${escape(error.message)}</td></tr>`;
        }
    }

    async function createPayment(membershipId) {
        const payment = await api.post("payment/create.php", {
            membership_id: membershipId
        });

        if (!payment?.id) {
            throw new Error("Không nhận được thông tin giao dịch.");
        }

        pendingPayment = payment;

        paymentDetails.innerHTML = `
            <p>Mã giao dịch: <strong>${escape(payment.id)}</strong></p>
            <p>Số tiền: <strong>${currency(payment.amount)}</strong></p>
            <p>Trạng thái: ${escape(statusLabel(payment.status))}</p>
        `;

        paymentPanel.classList.add("visible");
        hideFeedback(paymentFeedback);
        paymentPanel.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    grid.addEventListener("click", async event => {
        const button = event.target.closest("[data-plan-id]");
        if (!button || processing) return;

        const planId = Number(button.dataset.planId);
        if (!Number.isSafeInteger(planId) || planId <= 0) return;

        if (await loadCurrentMembership()) {
            feedback(
                planFeedback,
                "Bạn đang có gói hội viên Active. Không thể đăng ký thêm.",
                "error"
            );
            return;
        }

        setBusy(true);
        hideFeedback(planFeedback);

        try {
            const registration = await api.post("membership/register.php", {
                plan_id: planId
            });

            const membershipId = Number(registration.membership_id);

            if (!Number.isSafeInteger(membershipId) || membershipId <= 0) {
                throw new Error("Không nhận được mã đăng ký Membership.");
            }

            feedback(
                planFeedback,
                "Đăng ký gói thành công. Đang tạo giao dịch thanh toán...",
                "success"
            );

            await createPayment(membershipId);
            await loadPaymentHistory();

        } catch (error) {
            feedback(planFeedback, error.message, "error");
        } finally {
            setBusy(false);
        }
    });

    async function settlePayment(succeed) {
        if (!pendingPayment || processing) return;

        setBusy(true);

        try {
            const result = await api.post("payment/pay-demo.php", {
                payment_id: Number(pendingPayment.id),
                succeed: succeed
            });

            feedback(
                paymentFeedback,
                succeed
                    ? "Thanh toán thành công! Gói hội viên đã được kích hoạt."
                    : "Thanh toán thất bại. Gói tập vẫn đang chờ thanh toán.",
                succeed ? "success" : "error"
            );

            pendingPayment = null;

            await loadCurrentMembership();
            await loadPaymentHistory();

            if (succeed) {
                paymentDetails.innerHTML +=
                    "<p><strong>Membership đã được kích hoạt.</strong></p>";
            } else {
                paymentDetails.innerHTML +=
                    "<p>Bạn có thể thử tạo giao dịch mới cho Membership này.</p>";
            }

        } catch (error) {
            feedback(paymentFeedback, error.message, "error");
        } finally {
            setBusy(false);
        }
    }

    document.getElementById("paySuccessBtn")
        .addEventListener("click", () => settlePayment(true));

    document.getElementById("payFailBtn")
        .addEventListener("click", () => settlePayment(false));

    await Promise.all([
        loadPlans(),
        loadCurrentMembership(),
        loadPaymentHistory()
    ]);
});
</script>

</body>
</html>
PHP
