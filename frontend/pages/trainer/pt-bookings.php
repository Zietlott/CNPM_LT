<?php
/**
 * PERSONAL TRAINER WORKSPACE - BOOKINGS & SCHEDULES
 * FitZone - Fitness Center Membership & PT Booking App
 *
 * Role: trainer
 * ActiveNav: pt-bookings
 */

require_once __DIR__ . '/../../../backend/helpers/auth.php';
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../layouts/role-guard.php';

requireRole('trainer');

$pageTitle   = 'Quản lý Lịch & Booking PT';
$sidebarRole = 'trainer';
$activeNav   = 'pt-bookings';
$csrfToken   = getCsrfToken();
$trainerName = $_SESSION['full_name'] ?? 'Huấn luyện viên';
$extraCss    = [
    '../../assets/css/components.css',
    '../../assets/css/booking.css',
    '../../assets/css/pt-calendar.css',
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

            <!-- Page Intro & Header -->
            <div class="fz-page-header" style="background:#ffffff; padding:1.5rem; border-radius:16px; border:1px solid #e2e8f0; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                    <span class="fz-badge fz-badge--success mb-2">PERSONAL TRAINER WORKSPACE</span>
                    <h1 class="fz-page-header__title" style="color:#0f172a; margin-top:0.25rem; font-size:1.6rem; font-weight:800;">
                        Xin chào, <?= htmlspecialchars($trainerName, ENT_QUOTES, 'UTF-8') ?>! 👋
                    </h1>
                    <p class="fz-page-header__subtitle" style="color:#64748b; margin-bottom:0;">
                        Quản lý khung giờ làm việc, duyệt booking của hội viên và theo dõi lịch tập luyện cá nhân.
                    </p>
                </div>
                <div style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap;">
                    <button type="button" class="fz-btn fz-btn--primary" id="btnOpenScheduleModal">
                        ➕ Thêm ca làm việc
                    </button>
                    <button type="button" class="fz-btn fz-btn--secondary" id="btnOpenLeaveModal" style="border-color:#cbd5e1;">
                        🏖️ Đăng ký nghỉ / Khóa lịch
                    </button>
                    <a href="earnings.php" class="fz-btn fz-btn--secondary">
                        💰 Thu nhập PT
                    </a>
                </div>
            </div>

            <!-- Overview 4 KPI Summary Cards -->
            <div class="fz-summary-cards" style="margin-bottom: 1.5rem;">
                <div class="fz-summary-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="fz-summary-card__label">Lịch tập hôm nay</div>
                            <div class="fz-summary-card__value" id="kpiToday" style="color:#0f172a;">0</div>
                            <div class="fz-summary-card__sub" id="kpiTodaySub">Không có ca tập nào</div>
                        </div>
                        <div style="background:#e0f2fe; width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                            📅
                        </div>
                    </div>
                </div>

                <div class="fz-summary-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="fz-summary-card__label">Chờ bạn xác nhận</div>
                            <div class="fz-summary-card__value" id="kpiPending" style="color:#d97706;">0</div>
                            <div class="fz-summary-card__sub">Booking hội viên gửi</div>
                        </div>
                        <div style="background:#fef3c7; width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                            ⏳
                        </div>
                    </div>
                </div>

                <div class="fz-summary-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="fz-summary-card__label">Yêu cầu đổi lịch</div>
                            <div class="fz-summary-card__value" id="kpiChange" style="color:#2563eb;">0</div>
                            <div class="fz-summary-card__sub">Cần bạn duyệt lại</div>
                        </div>
                        <div style="background:#dbeafe; width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                            🔄
                        </div>
                    </div>
                </div>

                <div class="fz-summary-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="fz-summary-card__label">Buổi tập hoàn thành</div>
                            <div class="fz-summary-card__value fz-summary-card__value--accent" id="kpiCompleted" style="color:#16a34a;">0</div>
                            <div class="fz-summary-card__sub">Đã ghi nhận thu nhập</div>
                        </div>
                        <div style="background:#dcfce7; width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
                            🎯
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Mode Tabs -->
            <div class="view-mode-nav">
                <button type="button" class="view-mode-btn active" data-tab="calendar">
                    <span>📅</span> Lịch tuần của tôi
                </button>
                <button type="button" class="view-mode-btn" data-tab="bookings">
                    <span>📋</span> Danh sách Booking (<span id="tabCountActiveBookings">0</span>)
                </button>
                <button type="button" class="view-mode-btn" data-tab="history">
                    <span>📜</span> Lịch sử buổi tập (<span id="tabCountHistory">0</span>)
                </button>
                <button type="button" class="view-mode-btn" data-tab="leaves">
                    <span>🏖️</span> Ngày nghỉ & Khóa lịch (<span id="tabCountLeaves">0</span>)
                </button>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: WEEKLY CALENDAR VIEW                -->
            <!-- ========================================== -->
            <div id="tabContentCalendar" class="tab-pane active">
                <div class="calendar-nav-bar">
                    <div class="calendar-nav-controls">
                        <button type="button" class="fz-btn fz-btn--secondary fz-btn--sm" id="btnPrevWeek">
                            ◀ Tuần trước
                        </button>
                        <button type="button" class="fz-btn fz-btn--secondary fz-btn--sm" id="btnCurrentWeek">
                            Tuần hiện tại
                        </button>
                        <button type="button" class="fz-btn fz-btn--secondary fz-btn--sm" id="btnNextWeek">
                            Tuần sau ▶
                        </button>
                    </div>

                    <h2 class="calendar-range-title" id="calendarRangeTitle">
                        Đang tải tuần...
                    </h2>

                    <div class="calendar-legend">
                        <div class="legend-item"><span class="legend-dot open"></span> Còn trống</div>
                        <div class="legend-item"><span class="legend-dot confirmed"></span> Đã có booking</div>
                        <div class="legend-item"><span class="legend-dot pending"></span> Chờ xác nhận</div>
                        <div class="legend-item"><span class="legend-dot leave"></span> Nghỉ / Khóa</div>
                    </div>
                </div>

                <!-- 7-Column Weekly Grid -->
                <div class="calendar-week-grid" id="calendarWeekGrid">
                    <div style="grid-column: 1 / -1; padding: 3rem; text-align: center; color: #64748b;">
                        <span class="fz-spinner"></span> Đang tải lịch tuần...
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: BOOKING LIST VIEW                   -->
            <!-- ========================================== -->
            <div id="tabContentBookings" class="tab-pane" style="display:none;">
                <div class="card-box" style="margin-bottom: 1.5rem;">
                    <div class="card-box-header" style="flex-wrap:wrap; gap:1rem;">
                        <div>
                            <span class="fz-badge fz-badge--neutral mb-1">BOOKING MANAGEMENT</span>
                            <h2 class="card-box-title">Quản lý Booking của Hội viên</h2>
                        </div>
                        <div style="min-width: 260px;">
                            <input type="text" id="bookingSearchInput" class="fz-form-control" placeholder="🔍 Tìm theo tên, số điện thoại..." style="padding:0.45rem 0.85rem; font-size:0.875rem;">
                        </div>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="fz-filter-tabs" id="bookingFilterTabs">
                        <button type="button" class="fz-filter-tab active" data-filter="all">Tất cả (<span id="countAll">0</span>)</button>
                        <button type="button" class="fz-filter-tab" data-filter="pending">Chờ xác nhận (<span id="countPending">0</span>)</button>
                        <button type="button" class="fz-filter-tab" data-filter="confirmed">Đã xác nhận (<span id="countConfirmed">0</span>)</button>
                        <button type="button" class="fz-filter-tab" data-filter="change">Có yêu cầu đổi lịch (<span id="countChange">0</span>)</button>
                    </div>

                    <!-- Bookings List -->
                    <div id="trainerBookings" style="display:flex; flex-direction:column; gap:0.85rem;" aria-live="polite">
                        <div class="fz-loading-row">
                            <span class="fz-spinner"></span>
                            <span>Đang tải danh sách booking...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: SESSION HISTORY VIEW                -->
            <!-- ========================================== -->
            <div id="tabContentHistory" class="tab-pane" style="display:none;">
                <div class="card-box" style="margin-bottom: 1.5rem;">
                    <div class="card-box-header" style="flex-wrap:wrap; gap:1rem;">
                        <div>
                            <span class="fz-badge fz-badge--neutral mb-1">SESSION ARCHIVE</span>
                            <h2 class="card-box-title">Lịch sử các buổi tập đã qua</h2>
                        </div>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <select id="historyFilterSelect" class="fz-form-control" style="font-size:0.85rem; padding:0.4rem 0.75rem;">
                                <option value="all">Tất cả lịch sử</option>
                                <option value="completed">Buổi tập hoàn thành</option>
                                <option value="cancelled">Lịch đã hủy</option>
                                <option value="rejected">Lịch bị từ chối</option>
                            </select>
                        </div>
                    </div>

                    <div id="historyBookingsList" style="display:flex; flex-direction:column; gap:0.85rem;">
                        <div class="fz-loading-row">
                            <span class="fz-spinner"></span>
                            <span>Đang tải lịch sử buổi tập...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 4: LEAVES & TIME OFF VIEW              -->
            <!-- ========================================== -->
            <div id="tabContentLeaves" class="tab-pane" style="display:none;">
                <div class="card-box" style="margin-bottom: 1.5rem;">
                    <div class="card-box-header">
                        <div>
                            <span class="fz-badge fz-badge--neutral mb-1">TIME-OFF MANAGEMENT</span>
                            <h2 class="card-box-title">Danh sách ngày nghỉ & ca đã khóa</h2>
                        </div>
                        <button type="button" class="fz-btn fz-btn--primary fz-btn--sm" id="btnRegisterLeaveFromTab">
                            🏖️ Đăng ký nghỉ mới
                        </button>
                    </div>

                    <div id="trainerLeavesList" style="display:flex; flex-direction:column; gap:0.75rem;">
                        <div class="fz-loading-row">
                            <span class="fz-spinner"></span>
                            <span>Đang tải danh sách ngày nghỉ...</span>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="dashboard-footer" style="text-align:center; padding:1.5rem 0; color:#94a3b8; font-size:0.85rem;">
                © <?= date('Y') ?> FitZone Fitness Center. Nền tảng quản lý hội viên & đặt lịch Huấn luyện viên cá nhân.
            </footer>
        </main>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ========================================== -->
<!-- MODAL 1: THIẾT LẬP LỊCH LÀM VIỆC           -->
<!-- ========================================== -->
<div class="fz-modal-backdrop" id="modalSchedule">
    <div class="fz-modal-dialog">
        <div class="fz-modal-header">
            <h3 class="fz-modal-title" id="modalScheduleTitle">➕ Thiết lập khung giờ làm việc</h3>
            <button type="button" class="btn-close" id="btnCloseScheduleModal" aria-label="Đóng"></button>
        </div>
        <form id="formScheduleModal">
            <div class="fz-modal-body">
                <input type="hidden" id="modalScheduleId">

                <!-- Quick Presets -->
                <div style="margin-bottom:1.1rem;">
                    <label class="fz-form-label" style="font-size:0.8rem; text-transform:uppercase; color:#64748b;">Chọn nhanh ca mẫu:</label>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                        <button type="button" class="fz-btn fz-btn--secondary fz-btn--sm" data-modal-preset="morning">🌅 Sáng (08:00 - 10:00)</button>
                        <button type="button" class="fz-btn fz-btn--secondary fz-btn--sm" data-modal-preset="afternoon">☀️ Chiều (14:00 - 16:00)</button>
                        <button type="button" class="fz-btn fz-btn--secondary fz-btn--sm" data-modal-preset="evening">🌙 Tối (18:00 - 20:00)</button>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div class="fz-form-group" style="grid-column: 1 / -1; margin-bottom:0;">
                        <label for="modalWorkDate" class="fz-form-label">Ngày làm việc <span style="color:#ef4444;">*</span></label>
                        <input type="date" id="modalWorkDate" class="fz-form-control" required>
                    </div>
                    <div class="fz-form-group" style="margin-bottom:0;">
                        <label for="modalStartTime" class="fz-form-label">Giờ bắt đầu <span style="color:#ef4444;">*</span></label>
                        <input type="time" id="modalStartTime" class="fz-form-control" required>
                    </div>
                    <div class="fz-form-group" style="margin-bottom:0;">
                        <label for="modalEndTime" class="fz-form-label">Giờ kết thúc <span style="color:#ef4444;">*</span></label>
                        <input type="time" id="modalEndTime" class="fz-form-control" required>
                    </div>
                </div>

                <!-- Lặp lại theo thứ trong tuần -->
                <div id="recurringWrap" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; margin-top:1rem;">
                    <label style="display:flex; align-items:center; gap:8px; font-weight:700; font-size:0.9rem; color:#0f172a; cursor:pointer;">
                        <input type="checkbox" id="chkRecurring" style="width:16px; height:16px; accent-color:#16a34a;">
                        🔁 Lặp lại theo thứ trong tuần
                    </label>

                    <div id="recurringDetails" style="display:none; margin-top:10px; padding-top:10px; border-top:1px dashed #cbd5e1;">
                        <div style="font-size:0.8rem; color:#64748b; margin-bottom:6px;">Chọn các ngày trong tuần:</div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
                            <label style="font-size:0.85rem; font-weight:600;"><input type="checkbox" name="recurDay" value="1"> T2</label>
                            <label style="font-size:0.85rem; font-weight:600;"><input type="checkbox" name="recurDay" value="2"> T3</label>
                            <label style="font-size:0.85rem; font-weight:600;"><input type="checkbox" name="recurDay" value="3"> T4</label>
                            <label style="font-size:0.85rem; font-weight:600;"><input type="checkbox" name="recurDay" value="4"> T5</label>
                            <label style="font-size:0.85rem; font-weight:600;"><input type="checkbox" name="recurDay" value="5"> T6</label>
                            <label style="font-size:0.85rem; font-weight:600;"><input type="checkbox" name="recurDay" value="6"> T7</label>
                            <label style="font-size:0.85rem; font-weight:600;"><input type="checkbox" name="recurDay" value="0"> CN</label>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:0.85rem; color:#475569;">Thời gian áp dụng:</span>
                            <select id="selRecurWeeks" class="fz-form-control" style="width:auto; font-size:0.85rem; padding:3px 8px;">
                                <option value="1">1 tuần tới</option>
                                <option value="2" selected>2 tuần tới</option>
                                <option value="4">4 tuần tới (1 tháng)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="scheduleModalFeedback" style="margin-top:12px; display:none; padding:10px; border-radius:8px; font-size:0.85rem;"></div>
            </div>
            <div class="fz-modal-footer">
                <button type="button" class="fz-btn fz-btn--secondary" id="btnCancelScheduleModal">Hủy</button>
                <button type="submit" class="fz-btn fz-btn--primary" id="btnSaveScheduleModal">💾 Lưu khung giờ</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: ĐĂNG KÝ NGHỈ / KHÓA LỊCH          -->
<!-- ========================================== -->
<div class="fz-modal-backdrop" id="modalLeave">
    <div class="fz-modal-dialog">
        <div class="fz-modal-header">
            <h3 class="fz-modal-title">🏖️ Đăng ký nghỉ / Khóa lịch</h3>
            <button type="button" class="btn-close" id="btnCloseLeaveModal" aria-label="Đóng"></button>
        </div>
        <form id="formLeaveModal">
            <div class="fz-modal-body">
                <p style="font-size:0.85rem; color:#64748b; margin-bottom:1rem;">
                    Khi đăng ký nghỉ, các khung giờ còn trống trong ngày sẽ được tự động khóa lại để hội viên không thể đặt lịch.
                </p>

                <div class="fz-form-group">
                    <label for="leaveDate" class="fz-form-label">Ngày nghỉ <span style="color:#ef4444;">*</span></label>
                    <input type="date" id="leaveDate" class="fz-form-control" required>
                </div>

                <div style="margin-bottom:1rem;">
                    <label class="fz-form-label">Phạm vi nghỉ</label>
                    <div style="display:flex; gap:16px;">
                        <label style="display:flex; align-items:center; gap:6px; font-size:0.9rem; cursor:pointer;">
                            <input type="radio" name="leaveScope" value="all_day" checked style="accent-color:#16a34a;"> Nghỉ cả ngày
                        </label>
                        <label style="display:flex; align-items:center; gap:6px; font-size:0.9rem; cursor:pointer;">
                            <input type="radio" name="leaveScope" value="partial" style="accent-color:#16a34a;"> Nghỉ theo khung giờ
                        </label>
                    </div>
                </div>

                <div id="leavePartialTimes" style="display:none; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div class="fz-form-group" style="margin-bottom:0;">
                        <label for="leaveStartTime" class="fz-form-label">Từ giờ</label>
                        <input type="time" id="leaveStartTime" class="fz-form-control">
                    </div>
                    <div class="fz-form-group" style="margin-bottom:0;">
                        <label for="leaveEndTime" class="fz-form-label">Đến giờ</label>
                        <input type="time" id="leaveEndTime" class="fz-form-control">
                    </div>
                </div>

                <div class="fz-form-group" style="margin-bottom:0;">
                    <label for="leaveReason" class="fz-form-label">Lý do nghỉ <span style="color:#ef4444;">*</span></label>
                    <textarea id="leaveReason" class="fz-form-control" placeholder="Ví dụ: Bận việc gia đình, ốm, đi công tác..." required rows="3"></textarea>
                </div>

                <div id="leaveModalFeedback" style="margin-top:12px; display:none; padding:10px; border-radius:8px; font-size:0.85rem;"></div>
            </div>
            <div class="fz-modal-footer">
                <button type="button" class="fz-btn fz-btn--secondary" id="btnCancelLeaveModal">Hủy</button>
                <button type="submit" class="fz-btn fz-btn--primary" id="btnSaveLeaveModal">🏖️ Xác nhận nghỉ</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 3: CHI TIẾT BOOKING & HỘI VIÊN       -->
<!-- ========================================== -->
<div class="fz-modal-backdrop" id="modalBookingDetail">
    <div class="fz-modal-dialog">
        <div class="fz-modal-header">
            <h3 class="fz-modal-title">📋 Chi tiết lịch hẹn huấn luyện</h3>
            <button type="button" class="btn-close" id="btnCloseDetailModal" aria-label="Đóng"></button>
        </div>
        <div class="fz-modal-body" id="modalDetailContent">
            <!-- Dynamic Content -->
        </div>
        <div class="fz-modal-footer" id="modalDetailActions">
            <!-- Dynamic Actions -->
        </div>
    </div>
</div>

<script src="../../assets/js/api-client.js"></script>
<script src="../../assets/js/dashboard.js"></script>
<script src="../../assets/js/components.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    'use strict';

    const API = new FitzoneAPI('../../../backend/api/booking/');

    // DOM Elements
    const calendarGrid = document.getElementById("calendarWeekGrid");
    const calendarRangeTitle = document.getElementById("calendarRangeTitle");
    const btnPrevWeek = document.getElementById("btnPrevWeek");
    const btnCurrentWeek = document.getElementById("btnCurrentWeek");
    const btnNextWeek = document.getElementById("btnNextWeek");

    const bookingsElement = document.getElementById("trainerBookings");
    const historyElement = document.getElementById("historyBookingsList");
    const leavesElement = document.getElementById("trainerLeavesList");
    const searchInput = document.getElementById("bookingSearchInput");
    const historyFilterSelect = document.getElementById("historyFilterSelect");

    // Modals & Forms
    const modalSchedule = document.getElementById("modalSchedule");
    const formScheduleModal = document.getElementById("formScheduleModal");
    const modalWorkDate = document.getElementById("modalWorkDate");
    const modalStartTime = document.getElementById("modalStartTime");
    const modalEndTime = document.getElementById("modalEndTime");
    const modalScheduleId = document.getElementById("modalScheduleId");
    const chkRecurring = document.getElementById("chkRecurring");
    const recurringDetails = document.getElementById("recurringDetails");
    const recurringWrap = document.getElementById("recurringWrap");

    const modalLeave = document.getElementById("modalLeave");
    const formLeaveModal = document.getElementById("formLeaveModal");
    const leaveDate = document.getElementById("leaveDate");
    const leaveStartTime = document.getElementById("leaveStartTime");
    const leaveEndTime = document.getElementById("leaveEndTime");
    const leaveReason = document.getElementById("leaveReason");
    const leavePartialTimes = document.getElementById("leavePartialTimes");

    const modalBookingDetail = document.getElementById("modalBookingDetail");
    const modalDetailContent = document.getElementById("modalDetailContent");
    const modalDetailActions = document.getElementById("modalDetailActions");

    // State Variables
    let allSchedules = [];
    let allBookings = [];
    let allLeaves = [];
    let currentWeekOffset = 0; // 0 = current week, -1 = last week, 1 = next week
    let currentBookingFilter = 'all';
    let bookingSearchQuery = '';

    // Initialize Minimum Date
    const todayIso = new Date().toISOString().slice(0, 10);
    modalWorkDate.min = todayIso;
    modalWorkDate.value = todayIso;
    leaveDate.min = todayIso;
    leaveDate.value = todayIso;

    // ==========================================
    // TAB NAVIGATION
    // ==========================================
    document.querySelectorAll('.view-mode-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.view-mode-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');

            btn.classList.add('active');
            const targetTab = btn.dataset.tab;
            if (targetTab === 'calendar') document.getElementById('tabContentCalendar').style.display = 'block';
            if (targetTab === 'bookings') document.getElementById('tabContentBookings').style.display = 'block';
            if (targetTab === 'history') document.getElementById('tabContentHistory').style.display = 'block';
            if (targetTab === 'leaves') document.getElementById('tabContentLeaves').style.display = 'block';
        });
    });

    // ==========================================
    // DATA LOADING
    // ==========================================
    async function loadAllData() {
        try {
            const [schedulesRes, bookingsRes, leavesRes] = await Promise.all([
                API.get('trainer-schedules.php').catch(() => ({ data: [] })),
                API.get('trainer-bookings.php').catch(() => ({ data: [] })),
                API.get('trainer-leaves.php').catch(() => ({ data: [] }))
            ]);

            allSchedules = (schedulesRes && schedulesRes.data) ? schedulesRes.data : (Array.isArray(schedulesRes) ? schedulesRes : []);
            allBookings  = (bookingsRes && bookingsRes.data) ? bookingsRes.data : (Array.isArray(bookingsRes) ? bookingsRes : []);
            allLeaves    = (leavesRes && leavesRes.data) ? leavesRes.data : (Array.isArray(leavesRes) ? leavesRes : []);

            updateKpis(allBookings);
            renderCalendar();
            renderBookings();
            renderHistory();
            renderLeaves();
        } catch (err) {
            console.error("Load data error:", err);
            FitzoneToast.show("Không thể tải toàn bộ dữ liệu: " + err.message, "error");
        }
    }

    function updateKpis(bookings) {
        let todayCount = 0;
        let pendingCount = 0;
        let changeCount = 0;
        let completedCount = 0;
        let activeBookingsCount = 0;
        let historyCount = 0;

        bookings.forEach(b => {
            if (b.work_date === todayIso && b.status !== 'cancelled' && b.status !== 'rejected') {
                todayCount++;
            }
            if (b.status === 'pending') {
                pendingCount++;
                activeBookingsCount++;
            }
            if (b.status === 'confirmed') {
                activeBookingsCount++;
            }
            if (b.status === 'completed') {
                completedCount++;
                historyCount++;
            }
            if (['cancelled', 'rejected', 'expired'].includes(b.status)) {
                historyCount++;
            }
            if (b.pending_change_request_id) {
                changeCount++;
            }
        });

        document.getElementById('kpiToday').textContent = todayCount;
        document.getElementById('kpiTodaySub').textContent = todayCount > 0 ? `${todayCount} ca tập cần thực hiện` : 'Không có ca tập nào';
        document.getElementById('kpiPending').textContent = pendingCount;
        document.getElementById('kpiChange').textContent = changeCount;
        document.getElementById('kpiCompleted').textContent = completedCount;

        // Tab counts
        document.getElementById('tabCountActiveBookings').textContent = activeBookingsCount;
        document.getElementById('tabCountHistory').textContent = historyCount;
        document.getElementById('tabCountLeaves').textContent = allLeaves.length;

        // Sub filter counts
        document.getElementById('countAll').textContent = activeBookingsCount;
        document.getElementById('countPending').textContent = pendingCount;
        document.getElementById('countConfirmed').textContent = bookings.filter(b => b.status === 'confirmed').length;
        document.getElementById('countChange').textContent = changeCount;
    }

    // ==========================================
    // 1. WEEKLY CALENDAR RENDERING
    // ==========================================
    function getStartOfWeek(date, offsetWeeks = 0) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Monday is start
        d.setDate(diff + (offsetWeeks * 7));
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function renderCalendar() {
        const startMonday = getStartOfWeek(new Date(), currentWeekOffset);
        const weekDays = [];

        for (let i = 0; i < 7; i++) {
            const d = new Date(startMonday);
            d.setDate(startMonday.getDate() + i);
            weekDays.push(d);
        }

        const endSunday = weekDays[6];

        // Format Title: dd/mm - dd/mm/yyyy
        const formatDateShort = (d) => `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}`;
        calendarRangeTitle.textContent = `Tuần từ ${formatDateShort(startMonday)} đến ${formatDateShort(endSunday)}/${endSunday.getFullYear()}`;

        const dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];

        calendarGrid.innerHTML = weekDays.map((dateObj, idx) => {
            const dateStr = dateObj.toISOString().slice(0, 10);
            const isToday = dateStr === todayIso;

            // Check if this date has a registered leave
            const leaveRecord = allLeaves.find(l => l.leave_date === dateStr);

            // Filter schedules on this date
            const daySchedules = allSchedules.filter(s => s.work_date === dateStr);

            // Filter bookings on this date
            const dayBookings = allBookings.filter(b => b.work_date === dateStr && b.status !== 'cancelled' && b.status !== 'rejected');

            // Build cards for this day
            let cardsHtml = '';

            if (leaveRecord && !leaveRecord.start_time) {
                // Full day leave
                cardsHtml += `
                    <div class="cal-card leave" title="Lý do: ${FitzoneUI.escape(leaveRecord.reason)}">
                        <div class="cal-card-time">🏖️ CẢ NGÀY NGHỈ</div>
                        <div class="cal-card-title">${FitzoneUI.escape(leaveRecord.reason)}</div>
                        <div class="cal-card-sub">Lịch đã khóa nhận đặt</div>
                    </div>
                `;
            }

            // Render bookings on this date
            dayBookings.forEach(booking => {
                const isConfirmed = booking.status === 'confirmed';
                const isPending = booking.status === 'pending';
                const cardClass = isConfirmed ? 'confirmed' : (isPending ? 'pending' : 'cal-card');
                const badgeLabel = isConfirmed ? 'Đã xác nhận' : (isPending ? 'Chờ xác nhận' : booking.status);

                cardsHtml += `
                    <div class="cal-card ${cardClass}" data-click-booking="${booking.booking_id}">
                        <div class="cal-card-time">
                            <span>${booking.start_time.slice(0, 5)} - ${booking.end_time.slice(0, 5)}</span>
                            <span>${isConfirmed ? '🔵' : '🟡'}</span>
                        </div>
                        <div class="cal-card-title">👤 ${FitzoneUI.escape(booking.member_name)}</div>
                        <div class="cal-card-sub">${badgeLabel} ${booking.pending_change_request_id ? '· 🔄 Đổi ca' : ''}</div>
                    </div>
                `;
            });

            // Render open schedules that don't have active bookings
            daySchedules.forEach(slot => {
                const hasActiveBooking = dayBookings.some(b => b.schedule_id == slot.schedule_id);
                if (!hasActiveBooking) {
                    if (slot.status === 'available') {
                        cardsHtml += `
                            <div class="cal-card open" data-click-slot="${slot.schedule_id}" data-date="${dateStr}" data-start="${slot.start_time.slice(0, 5)}" data-end="${slot.end_time.slice(0, 5)}">
                                <div class="cal-card-time">
                                    <span>${slot.start_time.slice(0, 5)} - ${slot.end_time.slice(0, 5)}</span>
                                    <span>🟢</span>
                                </div>
                                <div class="cal-card-title">Còn trống (Mở)</div>
                                <div class="cal-card-sub">Nhấn để khóa / xóa</div>
                            </div>
                        `;
                    } else if (slot.status === 'unavailable' || slot.status === 'Closed') {
                        cardsHtml += `
                            <div class="cal-card leave" data-click-slot="${slot.schedule_id}">
                                <div class="cal-card-time">
                                    <span>${slot.start_time.slice(0, 5)} - ${slot.end_time.slice(0, 5)}</span>
                                    <span>🔒</span>
                                </div>
                                <div class="cal-card-title">Đã khóa</div>
                            </div>
                        `;
                    }
                }
            });

            if (!cardsHtml) {
                cardsHtml = `<div style="text-align:center; padding:1.5rem 0.5rem; color:#94a3b8; font-size:0.75rem;">Không có lịch</div>`;
            }

            return `
                <div class="calendar-day-col ${isToday ? 'is-today' : ''}">
                    <div class="calendar-day-header">
                        <div class="calendar-day-name">${dayNames[idx]}</div>
                        <div class="calendar-day-number">${dateObj.getDate()}</div>
                        ${isToday ? '<span class="fz-badge fz-badge--success" style="font-size:0.65rem; padding:1px 6px;">Hôm nay</span>' : ''}
                    </div>
                    <div class="calendar-day-body">
                        ${cardsHtml}
                        <button type="button" class="cal-add-btn" data-add-date="${dateStr}">
                            ➕ Thêm ca
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Week navigation controls
    btnPrevWeek.addEventListener('click', () => {
        currentWeekOffset--;
        renderCalendar();
    });
    btnCurrentWeek.addEventListener('click', () => {
        currentWeekOffset = 0;
        renderCalendar();
    });
    btnNextWeek.addEventListener('click', () => {
        currentWeekOffset++;
        renderCalendar();
    });

    // Calendar item clicks
    calendarGrid.addEventListener('click', function (e) {
        // 1. Click Add Slot on a specific day
        const addBtn = e.target.closest('[data-add-date]');
        if (addBtn) {
            openScheduleModal(addBtn.dataset.addDate);
            return;
        }

        // 2. Click Booking Card
        const bookingCard = e.target.closest('[data-click-booking]');
        if (bookingCard) {
            const bookingId = bookingCard.dataset.clickBooking;
            const booking = allBookings.find(b => b.booking_id == bookingId);
            if (booking) openBookingDetailModal(booking);
            return;
        }

        // 3. Click Open Slot
        const slotCard = e.target.closest('[data-click-slot]');
        if (slotCard) {
            const slotId = slotCard.dataset.clickSlot;
            handleSlotAction(slotId, slotCard.dataset.date, slotCard.dataset.start, slotCard.dataset.end);
            return;
        }
    });

    async function handleSlotAction(slotId, date, start, end) {
        const choice = await FitzoneModal.confirmAsync(
            "Tùy chọn khung giờ làm việc",
            `Khung giờ: ${start} - ${end} ngày ${date}\nBạn muốn thực hiện thao tác nào?`,
            {
                confirmLabel: "🗑️ Xóa vĩnh viễn",
                confirmClass: "fz-btn fz-btn--danger",
                cancelLabel: "🔒 Khóa nhận lịch"
            }
        );

        if (choice === true) {
            // Delete permanently
            try {
                await API.delete('trainer-schedules.php', { schedule_id: slotId, permanent: true });
                FitzoneToast.show("Đã xóa khung giờ làm việc thành công.", "success");
                await loadAllData();
            } catch (err) {
                FitzoneToast.show(err.message, "error");
            }
        } else if (choice === false) {
            // Deactivate
            try {
                await API.delete('trainer-schedules.php', { schedule_id: slotId });
                FitzoneToast.show("Đã khóa khung giờ làm việc thành công.", "success");
                await loadAllData();
            } catch (err) {
                FitzoneToast.show(err.message, "error");
            }
        }
    }

    // ==========================================
    // 2. BOOKINGS TAB RENDERING
    // ==========================================
    function renderBookings() {
        if (!bookingsElement) return;

        let activeList = allBookings.filter(b => ['pending', 'confirmed'].includes(b.status));

        // Filter tabs
        if (currentBookingFilter === 'pending') {
            activeList = activeList.filter(b => b.status === 'pending');
        } else if (currentBookingFilter === 'confirmed') {
            activeList = activeList.filter(b => b.status === 'confirmed');
        } else if (currentBookingFilter === 'change') {
            activeList = activeList.filter(b => Boolean(b.pending_change_request_id));
        }

        // Search
        if (bookingSearchQuery.trim() !== '') {
            const q = bookingSearchQuery.toLowerCase().trim();
            activeList = activeList.filter(b =>
                (b.member_name || '').toLowerCase().includes(q) ||
                (b.member_phone || '').toLowerCase().includes(q)
            );
        }

        if (activeList.length === 0) {
            bookingsElement.innerHTML = `
                <div class="fz-empty-state" style="border:1px dashed #e2e8f0; padding:2.5rem 1rem;">
                    <div class="fz-empty-state__icon">🔍</div>
                    <div class="fz-empty-state__title">Không có booking nào</div>
                    <div class="fz-empty-state__text">Hiện không có lịch tập nào khớp với bộ lọc của bạn.</div>
                </div>`;
            return;
        }

        bookingsElement.innerHTML = activeList.map(booking => {
            const actions = [];

            if (booking.status === 'pending') {
                actions.push(`
                    <button type="button" class="fz-btn fz-btn--primary fz-btn--sm" data-action="confirm" data-booking-id="${booking.booking_id}">
                        ✅ Xác nhận
                    </button>
                    <button type="button" class="fz-btn fz-btn--danger fz-btn--sm" data-action="reject" data-booking-id="${booking.booking_id}">
                        ❌ Từ chối
                    </button>
                `);
            }

            if (booking.status === 'confirmed') {
                actions.push(`
                    <button type="button" class="fz-btn fz-btn--primary fz-btn--sm" data-action="complete" data-booking-id="${booking.booking_id}">
                        🎯 Hoàn thành
                    </button>
                    <button type="button" class="fz-btn fz-btn--secondary fz-btn--sm" style="color:#ef4444; border-color:#fca5a5;" data-action="cancel" data-booking-id="${booking.booking_id}">
                        🚫 Hủy lịch
                    </button>
                `);
            }

            actions.push(`
                <button type="button" class="fz-btn fz-btn--secondary fz-btn--sm" data-action="view-detail" data-booking-id="${booking.booking_id}">
                    👁️ Chi tiết
                </button>
            `);

            const changeBox = booking.pending_change_request_id ? `
                <div style="margin-top:0.75rem; padding:0.75rem 1rem; border-radius:8px; background:#fffbeb; border:1px solid #fde68a;">
                    <div style="font-weight:700; color:#b45309; font-size:0.85rem;">🔔 Yêu cầu đổi ca tập:</div>
                    <div style="color:#78350f; font-size:0.825rem; margin-top:2px;">
                        Ngày mới: <strong>${FitzoneUI.formatDate(booking.requested_work_date)}</strong> · Giờ: <strong>${booking.requested_start_time.slice(0, 5)} - ${booking.requested_end_time.slice(0, 5)}</strong>
                    </div>
                    <div style="color:#92400e; font-size:0.8rem; margin-top:2px;"><em>Lý do: "${FitzoneUI.escape(booking.change_request_reason || 'Không có')}"</em></div>
                </div>
            ` : '';

            return `
                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:1.25rem; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <div style="flex:1; min-width:280px;">
                        <div style="display:flex; align-items:center; gap:0.65rem; margin-bottom:0.4rem; flex-wrap:wrap;">
                            <div style="background:#16a34a; color:#ffffff; width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.9rem;">
                                ${(booking.member_name || 'U').trim().charAt(0).toUpperCase()}
                            </div>
                            <h3 style="margin:0; font-size:1.05rem; font-weight:700; color:#0f172a;">
                                ${FitzoneUI.escape(booking.member_name)}
                            </h3>
                            ${FitzoneUI.statusBadge(booking.status)}
                            ${booking.member_phone ? `<a href="tel:${FitzoneUI.escape(booking.member_phone)}" style="font-size:0.85rem; color:#2563eb; text-decoration:none; font-weight:600;">📞 ${FitzoneUI.escape(booking.member_phone)}</a>` : ''}
                        </div>

                        <div style="color:#475569; font-size:0.875rem; line-height:1.6; margin-top:0.3rem;">
                            📅 Ngày tập: <strong style="color:#0f172a;">${FitzoneUI.formatDate(booking.work_date)}</strong> ·
                            ⏰ Giờ tập: <strong style="color:#0f172a;">${booking.start_time.slice(0, 5)} - ${booking.end_time.slice(0, 5)}</strong>
                            · Gói tập: <span style="font-weight:600; color:#0f172a;">${FitzoneUI.escape(booking.package_name || 'Gói PT')}</span>
                        </div>

                        ${booking.note ? `<div style="background:#f8fafc; border-left:3px solid #cbd5e1; padding:0.4rem 0.75rem; border-radius:4px; margin-top:0.4rem; font-size:0.825rem; color:#475569;"><strong>Ghi chú:</strong> ${FitzoneUI.escape(booking.note)}</div>` : ''}
                        ${changeBox}
                    </div>

                    <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; align-self:center;">
                        ${actions.join('')}
                    </div>
                </div>
            `;
        }).join('');
    }

    // Filter tabs click
    document.querySelectorAll('#bookingFilterTabs .fz-filter-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('#bookingFilterTabs .fz-filter-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentBookingFilter = tab.dataset.filter;
            renderBookings();
        });
    });

    // Search input
    searchInput.addEventListener('input', (e) => {
        bookingSearchQuery = e.target.value;
        renderBookings();
    });

    // ==========================================
    // 3. SESSION HISTORY RENDERING
    // ==========================================
    function renderHistory() {
        if (!historyElement) return;

        let historyList = allBookings.filter(b => ['completed', 'cancelled', 'rejected', 'expired'].includes(b.status));

        const selectedFilter = historyFilterSelect.value;
        if (selectedFilter !== 'all') {
            historyList = historyList.filter(b => b.status === selectedFilter);
        }

        if (historyList.length === 0) {
            historyElement.innerHTML = `
                <div class="fz-empty-state" style="border:1px dashed #e2e8f0; padding:2.5rem 1rem;">
                    <div class="fz-empty-state__icon">📜</div>
                    <div class="fz-empty-state__title">Chưa có lịch sử buổi tập</div>
                    <div class="fz-empty-state__text">Các buổi tập đã hoàn thành hoặc đã hủy sẽ hiển thị tại đây.</div>
                </div>`;
            return;
        }

        historyElement.innerHTML = historyList.map(booking => {
            const isCompleted = booking.status === 'completed';
            const iconBg = isCompleted ? '#dcfce7' : '#fee2e2';
            const iconColor = isCompleted ? '#16a34a' : '#ef4444';
            const icon = isCompleted ? '🎯' : '🚫';

            return `
                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:1.15rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div style="display:flex; align-items:center; gap:0.85rem;">
                        <div style="background:${iconBg}; color:${iconColor}; width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                            ${icon}
                        </div>
                        <div>
                            <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap;">
                                <strong style="color:#0f172a; font-size:1rem;">${FitzoneUI.escape(booking.member_name)}</strong>
                                ${FitzoneUI.statusBadge(booking.status)}
                                ${booking.fee_snapshot && Number(booking.fee_snapshot) > 0 ? `<span class="fz-badge fz-badge--success" style="font-size:0.75rem;">+${FitzoneUI.formatCurrency(booking.fee_snapshot)} đ</span>` : ''}
                            </div>
                            <div style="color:#64748b; font-size:0.85rem; margin-top:2px;">
                                📅 ${FitzoneUI.formatDate(booking.work_date)} (${booking.start_time.slice(0, 5)} - ${booking.end_time.slice(0, 5)})
                                ${booking.member_phone ? `· 📞 ${FitzoneUI.escape(booking.member_phone)}` : ''}
                            </div>
                            ${booking.cancel_reason ? `<div style="font-size:0.8rem; color:#991b1b; margin-top:3px;"><em>Lý do hủy/từ chối: "${FitzoneUI.escape(booking.cancel_reason)}"</em></div>` : ''}
                        </div>
                    </div>
                    <div>
                        <button type="button" class="fz-btn fz-btn--secondary fz-btn--sm" data-action="view-detail" data-booking-id="${booking.booking_id}">
                            Chi tiết
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    historyFilterSelect.addEventListener('change', renderHistory);

    // ==========================================
    // 4. LEAVES RENDERING
    // ==========================================
    function renderLeaves() {
        if (!leavesElement) return;

        if (allLeaves.length === 0) {
            leavesElement.innerHTML = `
                <div class="fz-empty-state" style="border:1px dashed #e2e8f0; padding:2rem 1rem;">
                    <div class="fz-empty-state__icon">🏖️</div>
                    <div class="fz-empty-state__title">Chưa có ngày nghỉ nào</div>
                    <div class="fz-empty-state__text">Khi cần nghỉ hoặc bận việc cá nhân, hãy đăng ký để hệ thống tự động khóa lịch hẹn.</div>
                </div>`;
            return;
        }

        leavesElement.innerHTML = allLeaves.map(leave => {
            const timeDesc = leave.start_time && leave.end_time
                ? `${leave.start_time.slice(0, 5)} - ${leave.end_time.slice(0, 5)}`
                : 'Cả ngày';

            return `
                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem 1.25rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div style="display:flex; align-items:center; gap:0.85rem;">
                        <div style="background:#fef2f2; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                            🏖️
                        </div>
                        <div>
                            <div style="display:flex; align-items:center; gap:0.6rem;">
                                <strong style="color:#0f172a; font-size:1rem;">${FitzoneUI.formatDate(leave.leave_date)}</strong>
                                <span class="fz-badge fz-badge--neutral">${timeDesc}</span>
                            </div>
                            <div style="color:#64748b; font-size:0.85rem; margin-top:2px;">
                                Lý do: <strong>${FitzoneUI.escape(leave.reason)}</strong>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="fz-btn fz-btn--danger fz-btn--sm" data-cancel-leave="${leave.id}">
                            🗑️ Hủy nghỉ
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Cancel Leave
    leavesElement.addEventListener('click', async function (e) {
        const btn = e.target.closest('[data-cancel-leave]');
        if (!btn) return;
        const leaveId = btn.dataset.cancelLeave;

        const confirmed = await FitzoneModal.confirmAsync(
            "Hủy ngày nghỉ",
            "Bạn có chắc chắn muốn hủy đăng ký nghỉ này?",
            { confirmLabel: "Xác nhận hủy", confirmClass: "fz-btn fz-btn--danger" }
        );
        if (!confirmed) return;

        try {
            await API.delete('trainer-leaves.php', { id: leaveId });
            FitzoneToast.show("Đã hủy đăng ký nghỉ thành công.", "success");
            await loadAllData();
        } catch (err) {
            FitzoneToast.show(err.message, "error");
        }
    });

    // ==========================================
    // 5. SCHEDULE MODAL LOGIC (ADD / RECURRING)
    // ==========================================
    function openScheduleModal(presetDate = null) {
        formScheduleModal.reset();
        modalScheduleId.value = '';
        document.getElementById('modalScheduleTitle').textContent = '➕ Thiết lập khung giờ làm việc';
        modalWorkDate.disabled = false;
        recurringWrap.style.display = 'block';
        chkRecurring.checked = false;
        recurringDetails.style.display = 'none';

        if (presetDate) {
            modalWorkDate.value = presetDate;
        } else {
            modalWorkDate.value = todayIso;
        }

        modalStartTime.value = '08:00';
        modalEndTime.value = '10:00';
        modalSchedule.style.display = 'flex';
    }

    document.getElementById('btnOpenScheduleModal').addEventListener('click', () => openScheduleModal());
    document.getElementById('btnCloseScheduleModal').addEventListener('click', () => modalSchedule.style.display = 'none');
    document.getElementById('btnCancelScheduleModal').addEventListener('click', () => modalSchedule.style.display = 'none');

    // Preset buttons
    document.querySelectorAll('[data-modal-preset]').forEach(btn => {
        btn.addEventListener('click', () => {
            const p = btn.dataset.modalPreset;
            if (p === 'morning') {
                modalStartTime.value = '08:00';
                modalEndTime.value = '10:00';
            } else if (p === 'afternoon') {
                modalStartTime.value = '14:00';
                modalEndTime.value = '16:00';
            } else if (p === 'evening') {
                modalStartTime.value = '18:00';
                modalEndTime.value = '20:00';
            }
        });
    });

    // Toggle recurring
    chkRecurring.addEventListener('change', () => {
        recurringDetails.style.display = chkRecurring.checked ? 'block' : 'none';
        if (chkRecurring.checked && modalWorkDate.value) {
            // Auto check current date day of week
            const d = new Date(modalWorkDate.value);
            const jsDay = d.getDay();
            const chk = document.querySelector(`input[name="recurDay"][value="${jsDay}"]`);
            if (chk) chk.checked = true;
        }
    });

    // Submit schedule form
    formScheduleModal.addEventListener('submit', async function (e) {
        e.preventDefault();
        const feedback = document.getElementById('scheduleModalFeedback');
        feedback.style.display = 'none';

        const workDate = modalWorkDate.value;
        const startTime = modalStartTime.value;
        const endTime = modalEndTime.value;

        // Validations
        if (endTime <= startTime) {
            feedback.style.display = 'block';
            feedback.style.background = '#fee2e2';
            feedback.style.color = '#991b1b';
            feedback.textContent = 'Giờ kết thúc phải lớn hơn giờ bắt đầu.';
            return;
        }

        const isRecurring = chkRecurring.checked;

        try {
            document.getElementById('btnSaveScheduleModal').disabled = true;

            if (isRecurring) {
                // Generate batch slots
                const selectedDays = Array.from(document.querySelectorAll('input[name="recurDay"]:checked')).map(cb => parseInt(cb.value));
                if (selectedDays.length === 0) {
                    feedback.style.display = 'block';
                    feedback.style.background = '#fee2e2';
                    feedback.style.color = '#991b1b';
                    feedback.textContent = 'Vui lòng chọn ít nhất một thứ trong tuần để lặp lại.';
                    document.getElementById('btnSaveScheduleModal').disabled = false;
                    return;
                }

                const weeks = parseInt(document.getElementById('selRecurWeeks').value) || 2;
                const batch = [];
                const startDate = new Date(workDate);

                for (let dayOffset = 0; dayOffset < weeks * 7; dayOffset++) {
                    const checkDate = new Date(startDate);
                    checkDate.setDate(startDate.getDate() + dayOffset);
                    const jsDay = checkDate.getDay();

                    if (selectedDays.includes(jsDay)) {
                        const dateStr = checkDate.toISOString().slice(0, 10);
                        batch.push({
                            work_date: dateStr,
                            start_time: startTime,
                            end_time: endTime
                        });
                    }
                }

                const res = await API.post('trainer-schedules.php', { batch: batch });
                FitzoneToast.show(res.message || `Đã tạo ${batch.length} khung giờ làm việc thành công!`, "success");
            } else {
                // Single slot
                await API.post('trainer-schedules.php', {
                    work_date: workDate,
                    start_time: startTime,
                    end_time: endTime
                });
                FitzoneToast.show("Đã thêm khung giờ làm việc thành công!", "success");
            }

            modalSchedule.style.display = 'none';
            await loadAllData();
        } catch (err) {
            feedback.style.display = 'block';
            feedback.style.background = '#fee2e2';
            feedback.style.color = '#991b1b';
            feedback.textContent = err.message || 'Lỗi khi lưu khung giờ.';
        } finally {
            document.getElementById('btnSaveScheduleModal').disabled = false;
        }
    });

    // ==========================================
    // 6. LEAVE MODAL LOGIC
    // ==========================================
    function openLeaveModal() {
        formLeaveModal.reset();
        leaveDate.value = todayIso;
        leavePartialTimes.style.display = 'none';
        document.getElementById('leaveModalFeedback').style.display = 'none';
        modalLeave.style.display = 'flex';
    }

    document.getElementById('btnOpenLeaveModal').addEventListener('click', openLeaveModal);
    document.getElementById('btnRegisterLeaveFromTab').addEventListener('click', openLeaveModal);
    document.getElementById('btnCloseLeaveModal').addEventListener('click', () => modalLeave.style.display = 'none');
    document.getElementById('btnCancelLeaveModal').addEventListener('click', () => modalLeave.style.display = 'none');

    document.querySelectorAll('input[name="leaveScope"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            leavePartialTimes.style.display = e.target.value === 'partial' ? 'grid' : 'none';
        });
    });

    formLeaveModal.addEventListener('submit', async function (e) {
        e.preventDefault();
        const feedback = document.getElementById('leaveModalFeedback');
        feedback.style.display = 'none';

        const isPartial = document.querySelector('input[name="leaveScope"]:checked').value === 'partial';
        const payload = {
            leave_date: leaveDate.value,
            reason: leaveReason.value.trim()
        };

        if (isPartial) {
            payload.start_time = leaveStartTime.value;
            payload.end_time = leaveEndTime.value;
            if (payload.end_time <= payload.start_time) {
                feedback.style.display = 'block';
                feedback.style.background = '#fee2e2';
                feedback.style.color = '#991b1b';
                feedback.textContent = 'Giờ kết thúc phải lớn hơn giờ bắt đầu.';
                return;
            }
        }

        try {
            document.getElementById('btnSaveLeaveModal').disabled = true;
            await API.post('trainer-leaves.php', payload);
            FitzoneToast.show("Đã đăng ký nghỉ thành công!", "success");
            modalLeave.style.display = 'none';
            await loadAllData();
        } catch (err) {
            feedback.style.display = 'block';
            feedback.style.background = '#fee2e2';
            feedback.style.color = '#991b1b';
            feedback.textContent = err.message || 'Lỗi khi đăng ký nghỉ.';
        } finally {
            document.getElementById('btnSaveLeaveModal').disabled = false;
        }
    });

    // ==========================================
    // 7. BOOKING DETAIL MODAL & ACTIONS
    // ==========================================
    function openBookingDetailModal(b) {
        modalDetailContent.innerHTML = `
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid #f1f5f9;">
                <div style="background:#16a34a; color:#fff; width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.1rem;">
                    ${(b.member_name || 'U').trim().charAt(0).toUpperCase()}
                </div>
                <div>
                    <h4 style="margin:0; font-size:1.15rem; font-weight:800; color:#0f172a;">${FitzoneUI.escape(b.member_name)}</h4>
                    <div style="margin-top:2px;">${FitzoneUI.statusBadge(b.status)}</div>
                </div>
            </div>

            <div class="detail-row">
                <span class="detail-label">Số điện thoại:</span>
                <span class="detail-val">
                    ${b.member_phone ? `<a href="tel:${FitzoneUI.escape(b.member_phone)}" style="color:#2563eb; text-decoration:none;">📞 ${FitzoneUI.escape(b.member_phone)}</a>` : 'Chưa cập nhật'}
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-val">${FitzoneUI.escape(b.member_email || 'Chưa có')}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Ngày tập:</span>
                <span class="detail-val">${FitzoneUI.formatDate(b.work_date)}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Khung giờ:</span>
                <span class="detail-val" style="color:#16a34a;">${b.start_time.slice(0, 5)} - ${b.end_time.slice(0, 5)} ${b.duration_minutes ? `(${b.duration_minutes} phút)` : ''}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Gói tập:</span>
                <span class="detail-val">${FitzoneUI.escape(b.package_name || 'Gói PT tiêu chuẩn')}</span>
            </div>
            ${b.fee_snapshot ? `
            <div class="detail-row">
                <span class="detail-label">Thu nhập dự kiến:</span>
                <span class="detail-val" style="color:#16a34a; font-size:1rem;">${FitzoneUI.formatCurrency(b.fee_snapshot)} đ</span>
            </div>` : ''}

            ${b.note ? `
            <div style="margin-top:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px;">
                <div style="font-size:0.8rem; font-weight:700; color:#64748b;">GHI CHÚ CỦA HỘI VIÊN:</div>
                <div style="font-size:0.875rem; color:#334155; margin-top:2px;">${FitzoneUI.escape(b.note)}</div>
            </div>` : ''}

            ${b.cancel_reason ? `
            <div style="margin-top:12px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:10px 12px;">
                <div style="font-size:0.8rem; font-weight:700; color:#991b1b;">LÝ DO HỦY / TỪ CHỐI:</div>
                <div style="font-size:0.875rem; color:#b91c1c; margin-top:2px;">${FitzoneUI.escape(b.cancel_reason)}</div>
            </div>` : ''}

            ${b.pending_change_request_id ? `
            <div style="margin-top:12px; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:12px;">
                <div style="font-weight:700; color:#b45309; font-size:0.9rem;">🔔 YÊU CẦU ĐỔI LỊCH TỪ HỘI VIÊN:</div>
                <div style="color:#78350f; font-size:0.875rem; margin-top:4px;">
                    Lịch mới đề xuất: <strong>${FitzoneUI.formatDate(b.requested_work_date)} (${b.requested_start_time.slice(0, 5)} - ${b.requested_end_time.slice(0, 5)})</strong>
                </div>
                <div style="color:#92400e; font-size:0.825rem; margin-top:2px;">
                    Lý do: <em>"${FitzoneUI.escape(b.change_request_reason || 'Không có')}"</em>
                </div>
                <div style="display:flex; gap:8px; margin-top:10px;">
                    <button type="button" class="fz-btn fz-btn--primary fz-btn--sm" data-change-action="approve" data-booking-id="${b.booking_id}" data-request-id="${b.pending_change_request_id}">
                        ✅ Chấp nhận đổi lịch
                    </button>
                    <button type="button" class="fz-btn fz-btn--danger fz-btn--sm" data-change-action="reject" data-booking-id="${b.booking_id}" data-request-id="${b.pending_change_request_id}">
                        ❌ Từ chối đổi lịch
                    </button>
                </div>
            </div>` : ''}
        `;

        // Render Action Buttons
        const btns = [];
        if (b.status === 'pending') {
            btns.push(`
                <button type="button" class="fz-btn fz-btn--primary" data-action="confirm" data-booking-id="${b.booking_id}">
                    ✅ Xác nhận booking
                </button>
                <button type="button" class="fz-btn fz-btn--danger" data-action="reject" data-booking-id="${b.booking_id}">
                    ❌ Từ chối
                </button>
            `);
        } else if (b.status === 'confirmed') {
            btns.push(`
                <button type="button" class="fz-btn fz-btn--primary" data-action="complete" data-booking-id="${b.booking_id}">
                    🎯 Đánh dấu hoàn thành
                </button>
                <button type="button" class="fz-btn fz-btn--danger" data-action="cancel" data-booking-id="${b.booking_id}">
                    🚫 Hủy lịch tập
                </button>
            `);
        }

        btns.push(`<button type="button" class="fz-btn fz-btn--secondary" id="btnCloseDetailFooter">Đóng</button>`);
        modalDetailActions.innerHTML = btns.join('');

        modalBookingDetail.style.display = 'flex';
    }

    document.getElementById('btnCloseDetailModal').addEventListener('click', () => modalBookingDetail.style.display = 'none');
    modalBookingDetail.addEventListener('click', (e) => {
        if (e.target.id === 'btnCloseDetailFooter') {
            modalBookingDetail.style.display = 'none';
        }
    });

    // Delegate Booking Actions across main views & modal
    document.addEventListener('click', async function (event) {
        // 1. View detail button
        const viewBtn = event.target.closest('[data-action="view-detail"]');
        if (viewBtn) {
            const bId = viewBtn.dataset.bookingId;
            const b = allBookings.find(x => x.booking_id == bId);
            if (b) openBookingDetailModal(b);
            return;
        }

        // 2. Change Request actions (approve / reject)
        const changeBtn = event.target.closest('[data-change-action]');
        if (changeBtn) {
            const action = changeBtn.dataset.changeAction;
            const bId = changeBtn.dataset.bookingId;
            const reqId = changeBtn.dataset.requestId;
            let reason = "";

            if (action === "reject") {
                reason = await FitzoneModal.prompt(
                    "Từ chối đổi lịch",
                    "Vui lòng nhập lý do từ chối yêu cầu đổi lịch của hội viên:",
                    "",
                    "Nhập lý do..."
                );
                if (reason === null) return;
                if (!reason.trim()) {
                    FitzoneToast.show("Vui lòng nhập lý do từ chối.", "warning");
                    return;
                }
            } else {
                const confirmed = await FitzoneModal.confirmAsync(
                    "Duyệt yêu cầu đổi lịch",
                    "Bạn đồng ý chuyển buổi tập của hội viên sang khung giờ mới?",
                    { confirmLabel: "Đồng ý chuyển lịch", confirmClass: "fz-btn fz-btn--primary" }
                );
                if (!confirmed) return;
            }

            try {
                await API.post('change-request-action.php', {
                    booking_id: bId,
                    request_id: reqId,
                    action: action,
                    reason: reason
                });
                FitzoneToast.show(action === 'approve' ? "Đã duyệt đổi lịch thành công! 🔄" : "Đã từ chối yêu cầu đổi lịch.", "success");
                modalBookingDetail.style.display = 'none';
                await loadAllData();
            } catch (err) {
                FitzoneToast.show(err.message, "error");
            }
            return;
        }

        // 3. Primary Booking actions: confirm, reject, complete, cancel
        const actBtn = event.target.closest('[data-action]');
        if (!actBtn || actBtn.dataset.action === 'view-detail') return;

        const action = actBtn.dataset.action;
        const bId = actBtn.dataset.bookingId;
        let reason = "";

        if (action === "reject" || action === "cancel") {
            const title = action === "reject" ? "Từ chối booking" : "Hủy lịch tập";
            const promptLabel = action === "reject" ? "Vui lòng nhập lý do từ chối lịch tập:" : "Vui lòng nhập lý do hủy buổi tập:";
            reason = await FitzoneModal.prompt(title, promptLabel, "", "Nhập lý do...");
            if (reason === null) return;
            if (!reason.trim()) {
                FitzoneToast.show("Vui lòng nhập lý do.", "warning");
                return;
            }
        } else if (action === "confirm") {
            const confirmed = await FitzoneModal.confirmAsync(
                "Xác nhận lịch tập",
                "Bạn có chắc chắn muốn xác nhận lịch tập với hội viên này?",
                { confirmLabel: "Xác nhận lịch", confirmClass: "fz-btn fz-btn--primary" }
            );
            if (!confirmed) return;
        } else if (action === "complete") {
            const confirmed = await FitzoneModal.confirmAsync(
                "Hoàn thành buổi tập",
                "Đánh dấu buổi tập đã hoàn thành? Hệ thống sẽ tự động tính toán và ghi nhận thu nhập cho bạn.",
                { confirmLabel: "Hoàn tất buổi tập", confirmClass: "fz-btn fz-btn--primary" }
            );
            if (!confirmed) return;
        }

        try {
            await API.post('trainer-action.php', {
                booking_id: bId,
                action: action,
                reason: reason
            });

            if (action === "complete") {
                FitzoneToast.show("Đã hoàn tất buổi tập và tự động ghi nhận thu nhập! 💰", "success");
            } else if (action === "confirm") {
                FitzoneToast.show("Đã xác nhận booking thành công!", "success");
            } else if (action === "cancel") {
                FitzoneToast.show("Đã hủy lịch tập thành công.", "info");
            } else {
                FitzoneToast.show("Đã từ chối booking.", "info");
            }

            modalBookingDetail.style.display = 'none';
            await loadAllData();
        } catch (err) {
            FitzoneToast.show(err.message, "error");
        }
    });

    // Close modals when clicking backdrop
    window.addEventListener('click', function (e) {
        if (e.target === modalSchedule) modalSchedule.style.display = 'none';
        if (e.target === modalLeave) modalLeave.style.display = 'none';
        if (e.target === modalBookingDetail) modalBookingDetail.style.display = 'none';
    });

    // Initial Load
    loadAllData();
});
</script>
</body>
</html>
