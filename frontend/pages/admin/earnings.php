<?php
/**
 * Thu nhập Huấn luyện viên – Admin View (Clean Light Theme)
 * FitZone - Fitness Center Membership & Personal Trainer Booking App
 *
 * Role: admin | staff
 * ActiveNav: earnings
 */

require_once __DIR__ . '/../../../backend/helpers/auth.php';
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../layouts/role-guard.php';

requireAnyRole(['admin', 'staff']);

$pageTitle   = 'Thu nhập PT';
$sidebarRole = $_SESSION['role'];
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
            <span class="fz-badge fz-badge--info mb-2">ADMINISTRATION</span>
            <h1 class="fz-page-header__title" style="color:#0f172a; margin-top:0.25rem;">📊 Quản lý Thu nhập Huấn luyện viên</h1>
            <p class="fz-page-header__subtitle" style="color:#64748b;">Báo cáo tổng hợp doanh thu và chi tiết thu nhập của tất cả Personal Trainer trong hệ thống</p>
        </div>
        <button class="fz-btn fz-btn--primary" id="btnExport">
            📥 Xuất Báo Cáo CSV
        </button>
    </div>

    <!-- Filter Bar -->
    <div class="fz-filter-bar" style="margin-bottom:1.5rem;">
        <div class="fz-form-group">
            <label class="fz-form-label" for="filterPt">Chọn Huấn luyện viên</label>
            <select id="filterPt" class="fz-form-control" style="width:230px">
                <option value="">Tất cả PT</option>
            </select>
        </div>
        <div class="fz-form-group">
            <label class="fz-form-label" for="filterMonth">Tháng báo cáo</label>
            <input type="month" id="filterMonth" class="fz-form-control"
                   value="<?= date('Y-m') ?>" style="width:170px">
        </div>
        <button class="fz-btn fz-btn--secondary" id="btnClearFilter">Xem tất cả</button>
        <button class="fz-btn fz-btn--primary"   id="btnApplyFilter">🔍 Lọc</button>
    </div>

    <!-- Summary by PT -->
    <div class="card-box" style="margin-bottom:1.5rem;">
        <div class="card-box-header">
            <h2 class="card-box-title">Tổng hợp thu nhập theo Huấn luyện viên</h2>
        </div>
        <div id="summaryArea">
            <div class="fz-loading-row"><span class="fz-spinner"></span><span>Đang tải tổng hợp…</span></div>
        </div>
    </div>

    <!-- Detail Table -->
    <div class="card-box" style="margin-top:0;">
        <div class="card-box-header">
            <div>
                <h2 class="card-box-title" id="tableHeading">Chi tiết các buổi tập hoàn thành</h2>
            </div>
            <div style="font-size:0.85rem; color:#64748b;">
                Bấm vào tiêu đề cột để sắp xếp
            </div>
        </div>
        <div id="tableArea">
            <div class="fz-loading-row"><span class="fz-spinner"></span><span>Đang tải danh sách…</span></div>
        </div>
        <div id="earningsPager" style="display:flex; gap:.4rem; align-items:center; margin-top:1.25rem; flex-wrap:wrap; justify-content:flex-end;"></div>
    </div>

</main>
</div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/dashboard.js"></script>
<script src="../../assets/js/components.js"></script>
<script>
(function () {
    'use strict';

    const API = new FitzoneAPI('../../../backend/api/');
    let allRows = [];

    function fmt(n)       { return FitzoneUI.formatCurrency(n) + ' đ'; }
    function fmtDate(dt)  { return FitzoneUI.formatDate(dt); }
    function fmtDur(min)  {
        if (!min) return '—';
        const h = Math.floor(min / 60), m = min % 60;
        return h ? `${h}g${m ? ' ' + m + 'p' : ''}` : `${m}p`;
    }

    async function load(ptId, month) {
        document.getElementById('summaryArea').innerHTML =
            '<div class="fz-loading-row"><span class="fz-spinner"></span><span>Đang tải…</span></div>';
        document.getElementById('tableArea').innerHTML =
            '<div class="fz-loading-row"><span class="fz-spinner"></span><span>Đang tải…</span></div>';
        document.getElementById('earningsPager').innerHTML = '';

        const params = {};
        if (ptId)   params.pt_id = ptId;
        if (month)  params.month = month;

        const res = await API.getQuery('earnings/admin-earnings.php', params);
        if (!res.success) {
            const errHtml = `
                <div class="fz-empty-state" style="border:1px dashed #e2e8f0; padding:2rem 1rem;">
                    <div class="fz-empty-state__icon">⚠️</div>
                    <div class="fz-empty-state__title">Lỗi tải dữ liệu</div>
                    <div class="fz-empty-state__text">${FitzoneUI.escape(res.message ?? 'Thử lại sau.')}</div>
                </div>`;
            document.getElementById('summaryArea').innerHTML = errHtml;
            document.getElementById('tableArea').innerHTML   = errHtml;
            return;
        }

        const { summary_by_pt, rows, trainers } = res.data;
        allRows = rows;

        // Populate trainer dropdown
        const sel = document.getElementById('filterPt');
        const curVal = sel.value;
        sel.innerHTML = '<option value="">Tất cả PT</option>';
        trainers.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.trainer_id;
            opt.textContent = t.full_name;
            if (String(t.trainer_id) === String(curVal)) opt.selected = true;
            sel.appendChild(opt);
        });

        // Heading
        const heading = month
            ? `Chi tiết thu nhập – Tháng ${month.slice(5)}/${month.slice(0,4)}`
            : 'Chi tiết thu nhập – Tất cả thời gian';
        document.getElementById('tableHeading').textContent = heading;

        renderSummary(summary_by_pt);
        renderTable(rows);
    }

    function renderSummary(summaryByPt) {
        const area = document.getElementById('summaryArea');
        if (!summaryByPt.length) {
            area.innerHTML = `
                <div class="fz-empty-state" style="border:1px dashed #e2e8f0; padding:2rem 1rem;">
                    <div class="fz-empty-state__icon">📭</div>
                    <div class="fz-empty-state__title">Không có dữ liệu thu nhập</div>
                    <div class="fz-empty-state__text">Chưa có thu nhập nào được ghi nhận cho khoảng thời gian này.</div>
                </div>`;
            return;
        }

        area.innerHTML = `
        <div class="fz-table-wrapper">
          <table class="fz-table">
            <thead>
              <tr>
                <th>Huấn luyện viên</th>
                <th class="text-center">Số buổi</th>
                <th class="text-end">Tổng thu nhập</th>
                <th class="text-end">TB / buổi</th>
              </tr>
            </thead>
            <tbody>
              ${summaryByPt.map(r => `
              <tr>
                <td><strong style="color:#0f172a;">${FitzoneUI.escape(r.trainer_name)}</strong></td>
                <td class="text-center">${r.total_sessions}</td>
                <td class="text-end col-amount" style="font-weight:700;">${fmt(r.total_earnings)}</td>
                <td class="text-end text-muted">${fmt(r.avg_per_session)}</td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>`;
    }

    function renderTable(rows) {
        const area  = document.getElementById('tableArea');
        const pager = document.getElementById('earningsPager');
        pager.innerHTML = '';

        if (!rows.length) {
            area.innerHTML = `
                <div class="fz-empty-state" style="border:1px dashed #e2e8f0; padding:2rem 1rem;">
                    <div class="fz-empty-state__icon">📭</div>
                    <div class="fz-empty-state__title">Không có chi tiết buổi tập</div>
                </div>`;
            return;
        }

        const pageSize = 15;
        const totalPages = Math.ceil(rows.length / pageSize);
        let curPage = 1;

        function renderPage() {
            const start = (curPage - 1) * pageSize;
            const slice = rows.slice(start, start + pageSize);
            area.innerHTML = `
            <div class="fz-table-wrapper">
              <table class="fz-table" id="detailTable">
                <thead>
                  <tr>
                    <th data-sortable>Ngày ghi nhận</th>
                    <th data-sortable>Huấn luyện viên</th>
                    <th data-sortable>Hội viên</th>
                    <th data-sortable>Thời gian tập</th>
                    <th data-sortable class="text-center">Thời lượng</th>
                    <th data-sortable class="text-end">Thu nhập</th>
                  </tr>
                </thead>
                <tbody>
                  ${slice.map(r => `
                  <tr>
                    <td class="text-muted">${fmtDate(r.recorded_at)}</td>
                    <td><strong>${FitzoneUI.escape(r.trainer_name)}</strong></td>
                    <td>${FitzoneUI.escape(r.member_name)}</td>
                    <td class="text-muted">${fmtDate(r.start_at)}</td>
                    <td class="text-center text-muted">${fmtDur(r.duration_minutes)}</td>
                    <td class="text-end col-amount" style="font-weight:700;">${fmt(r.amount)}</td>
                  </tr>`).join('')}
                </tbody>
              </table>
            </div>`;
            FitzoneTable.makeSortable(document.getElementById('detailTable'));
            renderPager();
        }

        function renderPager() {
            pager.innerHTML = '';
            if (totalPages <= 1) return;
            const info = document.createElement('span');
            info.className = 'text-muted me-2';
            info.style.fontSize = '.85rem';
            info.textContent = `Trang ${curPage}/${totalPages} (${rows.length} dòng)`;
            pager.appendChild(info);
            for (let p = 1; p <= totalPages; p++) {
                const btn = document.createElement('button');
                btn.className = `fz-btn fz-btn--sm ${p === curPage ? 'fz-btn--primary' : 'fz-btn--secondary'}`;
                btn.textContent = p;
                btn.addEventListener('click', () => { curPage = p; renderPage(); });
                pager.appendChild(btn);
            }
        }

        renderPage();
    }

    // ---- Events ----
    function getFilters() {
        return {
            ptId:  document.getElementById('filterPt').value,
            month: document.getElementById('filterMonth').value.trim(),
        };
    }

    document.getElementById('btnApplyFilter').addEventListener('click', () => {
        const { ptId, month } = getFilters();
        load(ptId, month);
    });
    document.getElementById('btnClearFilter').addEventListener('click', () => {
        document.getElementById('filterPt').value    = '';
        document.getElementById('filterMonth').value = '';
        load('', '');
    });

    // Export CSV
    document.getElementById('btnExport').addEventListener('click', () => {
        if (!allRows.length) {
            FitzoneToast.show('Không có dữ liệu để xuất.', 'warning');
            return;
        }
        const cols = ['Ngày ghi nhận', 'PT', 'Hội viên', 'Bắt đầu', 'Thời lượng (phút)', 'Thu nhập (VNĐ)'];
        const lines = [cols.join(',')];
        allRows.forEach(r => {
            lines.push([
                `"${r.recorded_at}"`,
                `"${r.trainer_name}"`,
                `"${r.member_name}"`,
                `"${r.start_at}"`,
                r.duration_minutes,
                r.amount,
            ].join(','));
        });
        const blob = new Blob(['\uFEFF' + lines.join('\n')], { type: 'text/csv;charset=utf-8' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `thu-nhap-pt-${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    });

    // Initial load
    const { ptId, month } = getFilters();
    load(ptId, month);
})();
</script>
</body>
</html>
