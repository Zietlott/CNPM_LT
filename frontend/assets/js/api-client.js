/**
 * FITZONE - API CLIENT (api-client.js)
 * Dùng chung cho tất cả các trang dashboard
 *
 * Cung cấp:
 *  - FitzoneAPI  : class client gọi các backend API endpoint
 *  - FitzoneUI   : helper xuất HTML, format ngày, escape, feedback
 *
 * Cách dùng:
 *   const api = new FitzoneAPI("../../../backend/api/", csrfToken);
 *   const data = await api.get("booking/mine.php");
 *   const result = await api.post("booking/create.php", { ... });
 */

"use strict";

/* ============================================================
   FitzoneAPI - HTTP client wrapper
   ============================================================ */
class FitzoneAPI {
    /**
     * @param {string} baseUrl   - đường dẫn tới thư mục backend/api/
     * @param {string} csrfToken - CSRF token lấy từ hidden input
     */
    constructor(baseUrl, csrfToken) {
        this._base  = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
        this._csrf  = csrfToken || "";
    }

    // ---- private: thực hiện fetch ----

    async _request(method, path, body = null) {
        const url     = this._base + path;
        const headers = {
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-Token":     this._csrf,
        };

        const options = { method, headers };

        if (body !== null) {
            if (body instanceof FormData) {
                // FormData: để browser tự đặt Content-Type với boundary
                options.body = body;
            } else {
                headers["Content-Type"] = "application/json";
                options.body = JSON.stringify(body);
            }
        }

        let response;
        try {
            response = await fetch(url, options);
        } catch (_) {
            throw new FitzoneError("Không thể kết nối máy chủ. Hãy kiểm tra kết nối mạng.", 0);
        }

        let payload;
        try {
            payload = await response.json();
        } catch (_) {
            throw new FitzoneError("Máy chủ trả về dữ liệu không hợp lệ.", response.status);
        }

        if (!response.ok || payload.success === false) {
            throw new FitzoneError(
                payload.message || "Yêu cầu thất bại.",
                response.status
            );
        }

        return payload.data ?? payload;
    }

    // ---- public: HTTP verbs ----

    get(path)              { return this._request("GET",    path); }
    post(path, body)       { return this._request("POST",   path, body); }
    put(path, body)        { return this._request("PUT",    path, body); }
    patch(path, body)      { return this._request("PATCH",  path, body); }
    delete(path, body)     { return this._request("DELETE", path, body); }

    // ---- public: convenience wrappers ----

    /** GET với query string từ object */
    getQuery(path, params = {}) {
        const qs = new URLSearchParams(params).toString();
        return this.get(path + (qs ? "?" + qs : ""));
    }

    /** Đặt lại CSRF token (khi server quay vòng token) */
    setCsrf(token) { this._csrf = token; }
}

/* ============================================================
   FitzoneError - error có HTTP status
   ============================================================ */
class FitzoneError extends Error {
    constructor(message, status) {
        super(message);
        this.name   = "FitzoneError";
        this.status = status;
    }
}

/* ============================================================
   FitzoneUI - helper xuất HTML & UI feedback
   ============================================================ */
const FitzoneUI = {

    /* ---- Escape HTML ---- */
    escape(value) {
        return String(value ?? "").replace(/[&<>"']/g, (ch) => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;",
        }[ch]));
    },

    /* ---- Format ngày tiếng Việt ---- */
    formatDate(value) {
        if (!value) return "";
        return new Intl.DateTimeFormat("vi-VN").format(
            new Date(value + "T00:00:00")
        );
    },

    /* ---- Format ngày + giờ ---- */
    formatDateTime(value) {
        if (!value) return "";
        return new Intl.DateTimeFormat("vi-VN", {
            day: "2-digit", month: "2-digit", year: "numeric",
            hour: "2-digit", minute: "2-digit",
        }).format(new Date(value));
    },

    /* ---- Format tiền VNĐ ---- */
    formatCurrency(amount) {
        return new Intl.NumberFormat("vi-VN", {
            style: "currency", currency: "VND",
        }).format(amount ?? 0);
    },

    /* ---- Nhãn trạng thái booking ---- */
    bookingStatusLabel(status) {
        const labels = {
            pending:   "Chờ xác nhận",
            confirmed: "Đã xác nhận",
            rejected:  "Đã từ chối",
            cancelled: "Đã hủy",
            completed: "Đã hoàn thành",
        };
        return labels[status] ?? "Không xác định";
    },

    /* ---- Nhãn gói hội viên ---- */
    membershipStatusLabel(status) {
        const labels = {
            active:   "Đang hoạt động",
            expired:  "Đã hết hạn",
            pending:  "Chờ kích hoạt",
            inactive: "Không hoạt động",
        };
        return labels[status] ?? status ?? "";
    },

    /* ---- Hiển thị feedback ---- */
    /**
     * @param {HTMLElement} el  - element feedback
     * @param {string} msg      - nội dung
     * @param {"success"|"error"|"info"|""} type
     */
    showFeedback(el, msg, type = "") {
        if (!el) return;
        el.textContent = msg;
        el.className   = "form-feedback" + (type ? " " + type : "");
        el.classList.add("is-visible");
    },

    clearFeedback(el) {
        if (!el) return;
        el.textContent = "";
        el.className   = "form-feedback";
        el.classList.remove("is-visible");
    },

    /* ---- Spinner HTML ---- */
    spinner(text = "Đang tải...") {
        return `<p class="booking-list-empty" aria-live="polite" aria-busy="true">${this.escape(text)}</p>`;
    },

    /* ---- Empty state ---- */
    empty(text = "Không có dữ liệu.") {
        return `<p class="booking-list-empty">${this.escape(text)}</p>`;
    },

    /* ---- Badge trạng thái ---- */
    statusBadge(status, label) {
        const e = this.escape.bind(this);
        return `<span class="booking-status booking-status-${e(status)}">${e(label ?? status)}</span>`;
    },
};

/* ---- Export (ES module hoặc global) ---- */
if (typeof module !== "undefined" && module.exports) {
    module.exports = { FitzoneAPI, FitzoneError, FitzoneUI };
} else {
    window.FitzoneAPI   = FitzoneAPI;
    window.FitzoneError = FitzoneError;
    window.FitzoneUI    = FitzoneUI;
}
