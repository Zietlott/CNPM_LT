<?php
/**
 * Thu nhập của Huấn luyện viên – Trainer Earnings Page
 * FitZone - Fitness Center Membership & Personal Trainer Booking App
 *
 * Role: trainer
 * ActiveNav: earnings
 */

require_once __DIR__ . '/../../../backend/helpers/auth.php';
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../layouts/role-guard.php';

requireRole('trainer');

$pageTitle   = 'Thu nhập của tôi';
$sidebarRole = 'trainer';
$activeNav   = 'earnings';
$csrfToken   = getCsrfToken();
$extraCss    = [
    '../../assets/css/components.css',
    '../../assets/css/earnings.css',
];

include __DIR__ . '/../../layouts/head.php';
?>
<body class="dashboard-page" style="background:#f8fafc; color:#0f172a;">
<div class="dashboard-layout">

<?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

<div class="main-area">

<?php include __DIR__ . '/../../layouts/topbar.php'; ?>

<main class="dashboard-content" id="mainContent" style="padding: 1.5rem 2rem;">
    <input type="hidden" id="pageCsrfToken" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Page Header -->
    <div class="fz-page-header" style="background:#ffffff; padding:1.5rem; border-radius:16px; border:1px solid #e2e8f0; margin-bottom:1.5rem;">
        <div>
            <span class="fz-badge fz-badge--success mb-2">TRAINER EARNINGS</span>
            <h1 class="fz-page-header__title" style="color:#0f172a; margin-top:0.25rem;">💰 Thu nhập của tôi</h1>
            <p class="fz-page-header__subtitle" style="color:#64748b;">
                Theo dõi thu nhập thực tế từ các ca tập đã hoàn thành, tiến độ thanh toán và đối soát chi tiết
            </p>
        </div>
        <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
            <a href="pt-bookings.php" class="fz-btn fz-btn--secondary">
                ← Về Lịch & Booking
            </a>
            <button class="fz-btn fz-btn--primary" id="btnExport">
                📥 Xuất Báo Cáo CSV
            </button>
        </div>
    </div>

    <!-- Filter Bar (Tháng, Trạng thái, Tìm kiếm) -->
    <div class="fz-earnings-filters">
        <div class="fz-earnings-filters__grid">
            <div class="filter-item">
                <label for="filterMonth">Lọc theo tháng:</label>
                <input type="month" id="filterMonth" value="<?= date('Y-m') ?>" style="width:180px;">
            </div>

            <div class="filter-item">
                <label for="filterStatus">Trạng thái thanh toán:</label>
                <select id="filterStatus" style="width:190px;">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="Paid">Đã thanh toán</option>
                    <option value="Pending">Chờ thanh toán</option>
                </select>
            </div>

            <div class="filter-item" style="flex:1; min-width:220px;">
                <label for="filterSearch">Tìm kiếm hội viên:</label>
                <input type="text" id="filterSearch" placeholder="Nhập họ tên hoặc SĐT hội viên...">
            </div>

            <div style="display:flex; gap:0.5rem; align-items:center;">
                <button class="fz-btn fz-btn--primary" id="btnApplyFilter">
                    🔍 Tìm kiếm
                </button>
                <button class="fz-btn fz-btn--secondary" id="btnClearFilter" title="Xem tất cả thời gian và xóa tìm kiếm">
                    Xem tất cả
                </button>
            </div>
        </div>
    </div>

    <!-- 4 Summary KPI Cards -->
    <div class="fz-summary-cards" id="summaryCards">
        <!-- 1. Tổng thu nhập -->
        <div class="fz-summary-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <div class="fz-summary-card__label">Tổng thu nhập</div>
                    <div class="fz-summary-card__value card-val--total" id="statTotal">—</div>
                    <div class="fz-summary-card__sub" id="statPeriodSub">Tổng hoàn thành trong kỳ</div>
                </div>
                <div class="fz-summary-card__icon-wrap" style="background:#dcfce7; color:#16a34a;">
                    💵
                </div>
            </div>
        </div>

        <!-- 2. Đã thanh toán -->
        <div class="fz-summary-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <div class="fz-summary-card__label">Đã thanh toán</div>
                    <div class="fz-summary-card__value card-val--paid" id="statPaid">—</div>
                    <div class="fz-summary-card__sub">Tiền PT đã thực nhận</div>
                </div>
                <div class="fz-summary-card__icon-wrap" style="background:#d1fae5; color:#059669;">
                    ✅
                </div>
            </div>
        </div>

        <!-- 3. Chờ thanh toán -->
        <div class="fz-summary-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <div class="fz-summary-card__label">Chờ thanh toán</div>
                    <div class="fz-summary-card__value card-val--pending" id="statPending">—</div>
                    <div class="fz-summary-card__sub">Chờ phòng gym chi trả</div>
                </div>
                <div class="fz-summary-card__icon-wrap" style="background:#fef3c7; color:#d97706;">
                    ⏳
                </div>
            </div>
        </div>

        <!-- 4. Số buổi hoàn thành -->
        <div class="fz-summary-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <div class="fz-summary-card__label">Số buổi hoàn thành</div>
                    <div class="fz-summary-card__value card-val--sessions" id="statSessions">—</div>
                    <div class="fz-summary-card__sub">Buổi tập hội viên đã hoàn tất</div>
                </div>
                <div class="fz-summary-card__icon-wrap" style="background:#e0f2fe; color:#0284c7;">
                    📋
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ thu nhập 6 tháng gần nhất -->
    <div class="fz-chart-box">
        <div class="fz-chart-header">
            <div>
                <h2 class="fz-chart-title">
                    <span>📊</span>
                    <span>Biểu đồ thu nhập 6 tháng gần nhất</span>
                </h2>
                <div style="font-size:0.85rem; color:#64748b; margin-top:0.25rem;">
                    Thống kê tổng thu nhập thực tế, phân tách tiền đã thanh toán và chờ thanh toán
                </div>
            </div>
            <div class="fz-chart-legend">
                <div class="fz-legend-item">
                    <span class="fz-legend-dot" style="background:#16a34a;"></span>
                    <span>Đã thanh toán</span>
                </div>
                <div class="fz-legend-item">
                    <span class="fz-legend-dot" style="background:#f59e0b;"></span>
                    <span>Chờ thanh toán</span>
                </div>
            </div>
        </div>
        <div class="fz-chart-canvas-wrapper">
            <canvas id="earningsChart"></canvas>
        </div>
    </div>

    <!-- Bảng lịch sử thu nhập chi tiết -->
    <div class="card-box" style="margin-top:0;">
        <div class="card-box-header" style="margin-bottom:1.25rem;">
            <div>
                <h2 class="card-box-title" id="tableHeading">Lịch sử thu nhập chi tiết</h2>
                <div style="font-size:0.85rem; color:#64748b; margin-top:0.25rem;">
                    Danh sách các buổi tập đã hoàn thành. <strong>Nhấp vào dòng bất kỳ</strong> để xem chi tiết buổi tập.
                </div>
            </div>
            <div id="tableRecordCount" style="font-size:0.875rem; color:#64748b; font-weight:600;"></div>
        </div>

        <div id="tableArea">
            <div class="fz-loading-row">
                <span class="fz-spinner"></span>
                <span>Đang tải dữ liệu thu nhập…</span>
            </div>
        </div>

        <div id="earningsPager" style="display:flex; gap:.4rem; align-items:center; margin-top:1.25rem; flex-wrap:wrap; justify-content:flex-end;"></div>
    </div>

</main>
</div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Modal Chi Tiết Buổi Tập -->
<div class="fz-modal-backdrop" id="earningDetailModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:1rem;">
    <div class="fz-modal" style="background:#ffffff; border-radius:16px; width:100%; max-width:540px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border:1px solid #e2e8f0; overflow:hidden;">
        <div style="padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <span style="font-size:1.3rem;">📋</span>
                <h3 style="font-size:1.15rem; font-weight:700; color:#0f172a; margin:0;">Chi tiết buổi tập & Thu nhập</h3>
            </div>
            <button type="button" id="btnDetailClose" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <div style="padding:1.5rem; max-height:calc(85vh - 120px); overflow-y:auto;" id="detailModalBody">
            <!-- Dynamic Content -->
        </div>

        <div style="padding:1rem 1.5rem; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; background:#f8fafc;">
            <button type="button" class="fz-btn fz-btn--secondary" id="btnDetailCloseFooter">Đóng</button>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/dashboard.js"></script>
<script src="../../assets/js/components.js"></script>

<script>
(function () {
    'use strict';

    const API   = new FitzoneAPI('../../../backend/api/');
    const tbody = document.getElementById('tableArea');
    const pager = document.getElementById('earningsPager');
    let allRows = [];
    let chartInstance = null;

    function fmt(n) {
        return FitzoneUI.formatCurrency(n) + ' đ';
    }

    // Modal elements
    const modalBackdrop = document.getElementById('earningDetailModal');
    const modalBody     = document.getElementById('detailModalBody');
    const btnClose1     = document.getElementById('btnDetailClose');
    const btnClose2     = document.getElementById('btnDetailCloseFooter');

    function closeModal() {
        modalBackdrop.style.display = 'none';
    }
    btnClose1.addEventListener('click', closeModal);
    btnClose2.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', (e) => {
        if (e.target === modalBackdrop) closeModal();
    });

    function openDetail(r) {
        const isPaid = r.payment_status === 'Paid';
        const badgeClass = isPaid ? 'badge-payment--paid' : 'badge-payment--pending';
        const badgeIcon  = isPaid ? '✅' : '⏳';

        modalBody.innerHTML = `
            <div class="fz-detail-grid">
                <div class="fz-detail-item" style="grid-column: 1 / -1;">
                    <div class="fz-detail-label">Hội viên thực hiện buổi tập</div>
                    <div class="fz-detail-value" style="font-size:1.1rem; color:#0f172a;">
                        <strong>${FitzoneUI.escape(r.member_name)}</strong>
                    </div>
                    <div style="font-size:0.85rem; color:#64748b; margin-top:0.25rem;">
                        ${r.member_phone ? '📞 ' + FitzoneUI.escape(r.member_phone) : ''}
                        ${r.member_email ? ' &bull; ✉️ ' + FitzoneUI.escape(r.member_email) : ''}
                    </div>
                </div>

                <div class="fz-detail-item">
                    <div class="fz-detail-label">Ngày & Khung giờ tập</div>
                    <div class="fz-detail-value">${FitzoneUI.escape(r.session_date)} (${FitzoneUI.escape(r.session_time)})</div>
                    <div style="font-size:0.8rem; color:#64748b; margin-top:0.2rem;">Thời lượng: ${r.duration_minutes || 60} phút</div>
                </div>

                <div class="fz-detail-item">
                    <div class="fz-detail-label">Số buổi tập</div>
                    <div class="fz-detail-value">${r.sessions_count || 1} buổi</div>
                    <div style="font-size:0.8rem; color:#64748b; margin-top:0.2rem;">Mã booking: #${r.booking_id}</div>
                </div>

                <div class="fz-detail-item">
                    <div class="fz-detail-label">Thu nhập PT được hưởng</div>
                    <div class="fz-detail-value fz-detail-value--amount">${fmt(r.amount)}</div>
                    <div style="font-size:0.8rem; color:#64748b; margin-top:0.2rem;">Giá niêm yết: ${fmt(r.fee_snapshot || r.amount)}</div>
                </div>

                <div class="fz-detail-item">
                    <div class="fz-detail-label">Trạng thái thanh toán</div>
                    <div style="margin-top:0.25rem;">
                        <span class="badge-payment ${badgeClass}">
                            <span>${badgeIcon}</span>
                            <span>${FitzoneUI.escape(r.payment_status_text)}</span>
                        </span>
                    </div>
                    <div style="font-size:0.8rem; color:#64748b; margin-top:0.35rem;">
                        ${isPaid && r.paid_at ? 'Đã chi trả: ' + r.paid_at : 'Đang chờ quyết toán kỳ này'}
                    </div>
                </div>

                <div class="fz-detail-item" style="grid-column: 1 / -1;">
                    <div class="fz-detail-label">Thời điểm hoàn tất & Ghi nhận</div>
                    <div class="fz-detail-value" style="font-size:0.875rem;">${FitzoneUI.escape(r.recorded_at)}</div>
                    ${r.note ? `<div style="margin-top:0.5rem; font-size:0.85rem; color:#475569; background:#ffffff; padding:0.5rem 0.75rem; border-radius:8px; border:1px solid #e2e8f0;"><strong>Ghi chú:</strong> ${FitzoneUI.escape(r.note)}</div>` : ''}
                </div>
            </div>
        `;
        modalBackdrop.style.display = 'flex';
    }

    // Load Data Function
    async function loadData() {
        const month  = document.getElementById('filterMonth').value.trim();
        const status = document.getElementById('filterStatus').value.trim();
        const search = document.getElementById('filterSearch').value.trim();

        tbody.innerHTML = `<div class="fz-loading-row"><span class="fz-spinner"></span><span>Đang tải dữ liệu thu nhập…</span></div>`;
        pager.innerHTML = '';
        document.getElementById('tableRecordCount').textContent = '';

        const params = {};
        if (month) params.month = month;
        if (status && status !== 'all') params.status = status;
        if (search) params.search = search;

        try {
            const res = await API.getQuery('earnings/my-earnings.php', params);
            // FitzoneAPI._request trả về payload.data trực tiếp
            const data = (res && res.summary) ? res : ((res && res.data) ? res.data : {});

            const summary = data.summary || {
                total_earnings: 0,
                paid_earnings: 0,
                pending_earnings: 0,
                total_sessions: 0,
                avg_per_session: 0
            };
            const chart = Array.isArray(data.chart) ? data.chart : [];
            const rows  = Array.isArray(data.rows)  ? data.rows  : [];
            allRows = rows;

            // 1. Cập nhật 4 thẻ thống kê (kể cả khi 0 đ vẫn hiển thị 0 đ, không hiển thị dấu -)
            document.getElementById('statTotal').textContent    = fmt(summary.total_earnings || 0);
            document.getElementById('statPaid').textContent     = fmt(summary.paid_earnings || 0);
            document.getElementById('statPending').textContent  = fmt(summary.pending_earnings || 0);
            document.getElementById('statSessions').textContent = (summary.total_sessions || 0) + ' buổi';

            const periodLabel = month
                ? `Tháng ${month.slice(5)}/${month.slice(0, 4)}`
                : 'Tất cả thời gian';
            document.getElementById('statPeriodSub').textContent = `Tổng hoàn thành trong kỳ (${periodLabel})`;

            // 2. Cập nhật Tiêu đề bảng
            let headingText = `Lịch sử thu nhập – ${periodLabel}`;
            if (status !== 'all') {
                headingText += ` (${status === 'Paid' ? 'Đã thanh toán' : 'Chờ thanh toán'})`;
            }
            if (search) {
                headingText += ` [Tìm: "${search}"]`;
            }
            document.getElementById('tableHeading').textContent = headingText;
            document.getElementById('tableRecordCount').textContent = rows.length > 0
                ? `Tổng cộng: ${rows.length} bản ghi`
                : '';

            // 3. Render Biểu đồ 6 tháng
            renderChart(chart);

            // 4. Render Bảng chi tiết
            renderTable(rows, periodLabel);

        } catch (err) {
            console.error('Lỗi khi tải thu nhập:', err);
            document.getElementById('statTotal').textContent    = '—';
            document.getElementById('statPaid').textContent     = '—';
            document.getElementById('statPending').textContent  = '—';
            document.getElementById('statSessions').textContent = '—';

            tbody.innerHTML = `
                <div class="fz-empty-state" style="border:1px dashed #ef4444; padding:2.5rem 1rem;">
                    <div class="fz-empty-state__icon">⚠️</div>
                    <div class="fz-empty-state__title" style="color:#b91c1c;">Không thể tải dữ liệu</div>
                    <div class="fz-empty-state__text">${FitzoneUI.escape(err.message || 'Lỗi kết nối máy chủ hoặc API.')}</div>
                </div>`;
        }
    }

    // Render Chart.js
    function renderChart(chartData) {
        if (!chartData || !chartData.length) return;
        const canvas = document.getElementById('earningsChart');
        if (!canvas) return;

        const labels   = chartData.map(c => c.label);
        const paidVals = chartData.map(c => c.paid_earnings || 0);
        const pendVals = chartData.map(c => c.pending_earnings || 0);

        if (chartInstance) {
            chartInstance.destroy();
        }

        const ctx = canvas.getContext('2d');
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Đã thanh toán (VNĐ)',
                        data: paidVals,
                        backgroundColor: '#16a34a',
                        borderRadius: 6,
                        stack: 'earningsStack'
                    },
                    {
                        label: 'Chờ thanh toán (VNĐ)',
                        data: pendVals,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                        stack: 'earningsStack'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Đã có custom HTML legend ở trên
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: function(context) {
                                const val = context.parsed.y || 0;
                                return `${context.dataset.label}: ${FitzoneUI.formatCurrency(val)} đ`;
                            },
                            footer: function(tooltipItems) {
                                let sum = 0;
                                tooltipItems.forEach(item => {
                                    sum += item.parsed.y || 0;
                                });
                                return `Tổng thu nhập tháng: ${FitzoneUI.formatCurrency(sum)} đ`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#64748b',
                            font: { weight: '600' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 1000000,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            color: '#64748b',
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' Tr';
                                if (value >= 1000) return (value / 1000) + ' K';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Render Bảng Lịch Sử Thu Nhập Chi Tiết
    function renderTable(rows, periodLabel) {
        if (!rows.length) {
            tbody.innerHTML = `
                <div class="fz-empty-state" style="border:1px dashed #e2e8f0; padding:3rem 1rem;">
                    <div class="fz-empty-state__icon">📭</div>
                    <div class="fz-empty-state__title">Chưa có dữ liệu thu nhập</div>
                    <div class="fz-empty-state__text">
                        Chưa có buổi tập nào hoàn thành được ghi nhận thu nhập trong khoảng thời gian này (${FitzoneUI.escape(periodLabel || 'kỳ được chọn')}).
                        Bạn có thể chọn tháng khác hoặc nhấn nút <strong>"Xem tất cả"</strong>.
                    </div>
                </div>`;
            return;
        }

        const pageSize = 10;
        const totalPages = Math.ceil(rows.length / pageSize);
        let curPage = 1;

        function renderPage() {
            const start = (curPage - 1) * pageSize;
            const slice = rows.slice(start, start + pageSize);

            const html = `
            <div class="fz-table-wrapper">
              <table class="fz-table" id="earningsTable">
                <thead>
                  <tr>
                    <th style="width:60px;" class="text-center">STT</th>
                    <th data-sortable>Ngày tập</th>
                    <th data-sortable>Hội viên</th>
                    <th data-sortable class="text-center" style="width:110px;">Số buổi</th>
                    <th data-sortable class="text-end">Thu nhập PT</th>
                    <th data-sortable class="text-center" style="width:160px;">Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                  ${slice.map((r, idx) => {
                      const stt = start + idx + 1;
                      const isPaid = r.payment_status === 'Paid';
                      const badgeClass = isPaid ? 'badge-payment--paid' : 'badge-payment--pending';
                      const badgeIcon  = isPaid ? '✅' : '⏳';
                      return `
                      <tr class="clickable-row" data-id="${r.earning_id}" title="Bấm để xem chi tiết buổi tập">
                        <td class="text-center text-muted" style="font-weight:600;">${stt}</td>
                        <td>
                            <div style="font-weight:600; color:#0f172a;">${FitzoneUI.escape(r.session_date)}</div>
                            <div style="font-size:0.8rem; color:#64748b;">${FitzoneUI.escape(r.session_time)}</div>
                        </td>
                        <td>
                            <strong style="color:#0f172a; font-size:0.95rem;">${FitzoneUI.escape(r.member_name)}</strong>
                            ${r.member_phone ? `<div style="font-size:0.8rem; color:#64748b;">${FitzoneUI.escape(r.member_phone)}</div>` : ''}
                        </td>
                        <td class="text-center" style="font-weight:600; color:#475569;">
                            ${r.sessions_count || 1}
                        </td>
                        <td class="text-end" style="font-size:1.05rem; font-weight:700; color:#16a34a;">
                            ${fmt(r.amount)}
                        </td>
                        <td class="text-center">
                            <span class="badge-payment ${badgeClass}">
                                <span>${badgeIcon}</span>
                                <span>${FitzoneUI.escape(r.payment_status_text)}</span>
                            </span>
                        </td>
                      </tr>`;
                  }).join('')}
                </tbody>
              </table>
            </div>`;

            tbody.innerHTML = html;
            FitzoneTable.makeSortable(document.getElementById('earningsTable'));

            // Gắn sự kiện click dòng mở modal chi tiết
            const tableEl = document.getElementById('earningsTable');
            tableEl.querySelectorAll('tbody tr.clickable-row').forEach(rowEl => {
                rowEl.addEventListener('click', () => {
                    const eId = parseInt(rowEl.dataset.id, 10);
                    const item = rows.find(x => x.earning_id === eId);
                    if (item) openDetail(item);
                });
            });

            renderPager();
        }

        function renderPager() {
            pager.innerHTML = '';
            if (totalPages <= 1) return;

            const info = document.createElement('span');
            info.className = 'text-muted me-2';
            info.style.fontSize = '.85rem';
            info.textContent = `Trang ${curPage}/${totalPages} (${rows.length} buổi tập)`;
            pager.appendChild(info);

            for (let p = 1; p <= totalPages; p++) {
                const btn = document.createElement('button');
                btn.className = `fz-btn fz-btn--sm ${p === curPage ? 'fz-btn--primary' : 'fz-btn--secondary'}`;
                btn.textContent = p;
                btn.addEventListener('click', () => {
                    curPage = p;
                    renderPage();
                });
                pager.appendChild(btn);
            }
        }

        renderPage();
    }

    // ---- Sự kiện Bộ Lọc & Tìm Kiếm ----
    document.getElementById('btnApplyFilter').addEventListener('click', loadData);

    document.getElementById('filterMonth').addEventListener('change', loadData);
    document.getElementById('filterStatus').addEventListener('change', loadData);
    document.getElementById('filterSearch').addEventListener('keydown', e => {
        if (e.key === 'Enter') loadData();
    });

    document.getElementById('btnClearFilter').addEventListener('click', () => {
        document.getElementById('filterMonth').value = '';
        document.getElementById('filterStatus').value = 'all';
        document.getElementById('filterSearch').value = '';
        loadData();
    });

    // Xuất CSV / Excel Chuẩn UTF-8
    document.getElementById('btnExport').addEventListener('click', () => {
        if (!allRows.length) {
            FitzoneToast.show('Không có dữ liệu thu nhập để xuất theo bộ lọc hiện tại.', 'warning');
            return;
        }

        const headers = [
            'STT',
            'Ngày tập',
            'Khung giờ',
            'Tên hội viên',
            'Số điện thoại',
            'Số buổi',
            'Thu nhập PT (VNĐ)',
            'Giá gốc buổi tập (VNĐ)',
            'Trạng thái thanh toán',
            'Ngày ghi nhận',
            'Ngày chi trả'
        ];

        const lines = [headers.join(',')];

        allRows.forEach((r, idx) => {
            const stt = idx + 1;
            const rowValues = [
                stt,
                `"${r.session_date || ''}"`,
                `"${r.session_time || ''}"`,
                `"${(r.member_name || '').replace(/"/g, '""')}"`,
                `"${r.member_phone || ''}"`,
                r.sessions_count || 1,
                r.amount,
                r.fee_snapshot || r.amount,
                `"${r.payment_status_text || ''}"`,
                `"${r.recorded_at || ''}"`,
                `"${r.paid_at || ''}"`
            ];
            lines.push(rowValues.join(','));
        });

        // Thêm ký tự UTF-8 BOM (\uFEFF) để Microsoft Excel mở tiếng Việt không bị lỗi font
        const blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');

        const monthVal = document.getElementById('filterMonth').value.trim();
        const suffix = monthVal ? `thang-${monthVal}` : 'tat-ca';
        a.href     = url;
        a.download = `fitzone-thu-nhap-pt-${suffix}-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        FitzoneToast.show(`Đã xuất thành công ${allRows.length} bản ghi thu nhập ra file CSV!`, 'success');
    });

    // Tải dữ liệu ban đầu
    loadData();
})();
</script>
</body>
</html>
