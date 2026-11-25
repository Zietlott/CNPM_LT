/**
 * FitZone – Shared UI Component JavaScript
 * Requires: api-client.js loaded first
 *
 * Exports (globals):
 *   FitzoneToast   – toast notification manager
 *   FitzoneModal   – confirm/alert modal manager
 *   FitzoneTable   – table sort + pagination helper
 */

/* =========================================================
   TOAST
   ========================================================= */
const FitzoneToast = (() => {
    let container = null;

    function getContainer() {
        if (!container) {
            container = document.getElementById('fz-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'fz-toast-container';
                document.body.appendChild(container);
            }
        }
        return container;
    }

    const ICONS = {
        success: '✅',
        error:   '❌',
        warning: '⚠️',
        info:    'ℹ️',
    };

    function show(message, type = 'info', duration = 4000) {
        const c = getContainer();
        const toast = document.createElement('div');
        toast.className = `fz-toast fz-toast--${type}`;
        toast.innerHTML = `
            <span class="fz-toast__icon" aria-hidden="true">${ICONS[type] ?? 'ℹ️'}</span>
            <span class="fz-toast__body">${FitzoneUI.escape(message)}</span>
            <button class="fz-toast__close" aria-label="Đóng">×</button>
        `;
        toast.querySelector('.fz-toast__close').addEventListener('click', () => dismiss(toast));
        c.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => dismiss(toast), duration);
        }
        return toast;
    }

    function dismiss(toast) {
        if (!toast || !toast.parentNode) return;
        toast.classList.add('hiding');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }

    return { show };
})();

/* =========================================================
   MODAL
   ========================================================= */
const FitzoneModal = (() => {
    let overlay = null;

    function ensureDOM() {
        if (overlay) return;
        overlay = document.createElement('div');
        overlay.className = 'fz-modal-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.innerHTML = `
            <div class="fz-modal-box">
                <div class="fz-modal-header">
                    <h2 class="fz-modal-title" id="fzModalTitle">Xác nhận</h2>
                    <button class="fz-modal-close" aria-label="Đóng">×</button>
                </div>
                <div class="fz-modal-body" id="fzModalBody"></div>
                <div class="fz-modal-footer" id="fzModalFooter"></div>
            </div>
        `;
        overlay.querySelector('.fz-modal-close').addEventListener('click', hide);
        overlay.addEventListener('click', e => { if (e.target === overlay) hide(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') hide(); });
        document.body.appendChild(overlay);
    }

    /**
     * Show a confirm modal.
     * @param {string} title
     * @param {string} body   - HTML allowed
     * @param {function} onConfirm
     * @param {object} opts   - { confirmLabel, confirmClass, cancelLabel }
     */
    function confirm(title, body, onConfirm, opts = {}) {
        ensureDOM();
        const {
            confirmLabel = 'Xác nhận',
            confirmClass = 'fz-btn fz-btn--primary',
            cancelLabel  = 'Huỷ',
        } = opts;

        overlay.querySelector('#fzModalTitle').textContent = title;
        overlay.querySelector('#fzModalBody').innerHTML    = body;
        overlay.setAttribute('aria-labelledby', 'fzModalTitle');

        const footer = overlay.querySelector('#fzModalFooter');
        footer.innerHTML = '';

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'fz-btn fz-btn--secondary';
        cancelBtn.textContent = cancelLabel;
        cancelBtn.addEventListener('click', hide);

        const confirmBtn = document.createElement('button');
        confirmBtn.className = confirmClass;
        confirmBtn.textContent = confirmLabel;
        confirmBtn.addEventListener('click', async () => {
            confirmBtn.disabled = true;
            confirmBtn.textContent = '…';
            try { await onConfirm(); } finally { hide(); }
        });

        footer.appendChild(cancelBtn);
        footer.appendChild(confirmBtn);
        open();
    }

    /** Show a simple alert modal (no cancel button). */
    function alert(title, body) {
        return new Promise(resolve => {
            confirm(title, body, resolve, {
                confirmLabel: 'Đóng',
                confirmClass: 'fz-btn fz-btn--secondary',
                cancelLabel: null,
            });
            // remove cancel btn
            const footer = overlay.querySelector('#fzModalFooter');
            const btns = footer.querySelectorAll('button');
            if (btns.length > 1) btns[0].remove();
        });
    }

    function open() {
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        overlay.querySelector('.fz-modal-box').focus?.();
    }

    function hide() {
        if (!overlay) return;
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    /**
     * Show an async confirm modal returning a Promise<boolean>.
     */
    function confirmAsync(title, body, opts = {}) {
        return new Promise(resolve => {
            confirm(
                title,
                body,
                () => resolve(true),
                {
                    ...opts,
                    onCancel: () => resolve(false)
                }
            );
        });
    }

    /**
     * Show a prompt modal returning a Promise<string|null>.
     */
    function prompt(title, message, defaultValue = '', placeholder = 'Nhập thông tin...') {
        return new Promise(resolve => {
            ensureDOM();
            const inputId = 'fzPromptInput_' + Date.now();
            const html = `
                <p style="margin-bottom:0.75rem;">${FitzoneUI.escape(message)}</p>
                <div class="fz-form-group" style="margin-bottom:0;">
                    <textarea id="${inputId}" class="fz-form-control" rows="3" placeholder="${FitzoneUI.escape(placeholder)}">${FitzoneUI.escape(defaultValue)}</textarea>
                </div>
            `;

            overlay.querySelector('#fzModalTitle').textContent = title;
            overlay.querySelector('#fzModalBody').innerHTML    = html;
            overlay.setAttribute('aria-labelledby', 'fzModalTitle');

            const footer = overlay.querySelector('#fzModalFooter');
            footer.innerHTML = '';

            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'fz-btn fz-btn--secondary';
            cancelBtn.textContent = 'Huỷ';
            cancelBtn.addEventListener('click', () => {
                hide();
                resolve(null);
            });

            const confirmBtn = document.createElement('button');
            confirmBtn.className = 'fz-btn fz-btn--primary';
            confirmBtn.textContent = 'Xác nhận';
            confirmBtn.addEventListener('click', () => {
                const val = overlay.querySelector(`#${inputId}`)?.value?.trim() ?? '';
                hide();
                resolve(val);
            });

            footer.appendChild(cancelBtn);
            footer.appendChild(confirmBtn);
            open();
            setTimeout(() => {
                const input = overlay.querySelector(`#${inputId}`);
                input?.focus();
                input?.select();
            }, 50);
        });
    }

    return { confirm, confirmAsync, alert, prompt, hide };
})();

/* =========================================================
   TABLE SORT & PAGINATION
   ========================================================= */
const FitzoneTable = (() => {
    /**
     * Make a <table> sortable by clicking its <th> elements.
     * @param {HTMLTableElement} table
     */
    function makeSortable(table) {
        const ths = table.querySelectorAll('thead th[data-sortable]');
        ths.forEach((th, colIdx) => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                const asc = th.getAttribute('data-sort') !== 'asc';
                ths.forEach(h => h.removeAttribute('data-sort'));
                th.setAttribute('data-sort', asc ? 'asc' : 'desc');
                sortTableByCol(table, colIdx, asc);
            });
        });
    }

    function sortTableByCol(table, colIdx, asc) {
        const tbody = table.querySelector('tbody');
        const rows  = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const aText = a.cells[colIdx]?.dataset.value ?? a.cells[colIdx]?.textContent.trim() ?? '';
            const bText = b.cells[colIdx]?.dataset.value ?? b.cells[colIdx]?.textContent.trim() ?? '';
            const aNum  = parseFloat(aText.replace(/[^0-9.-]/g, ''));
            const bNum  = parseFloat(bText.replace(/[^0-9.-]/g, ''));
            if (!isNaN(aNum) && !isNaN(bNum)) return asc ? aNum - bNum : bNum - aNum;
            return asc ? aText.localeCompare(bText, 'vi') : bText.localeCompare(aText, 'vi');
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    /**
     * Simple client-side pagination.
     * @param {string} tableId     - table element id
     * @param {string} pagerId     - container for pager buttons
     * @param {number} pageSize
     */
    function paginate(tableId, pagerId, pageSize = 15) {
        const table = document.getElementById(tableId);
        const pager = document.getElementById(pagerId);
        if (!table || !pager) return;

        let currentPage = 1;

        function render() {
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const total = rows.length;
            const totalPages = Math.ceil(total / pageSize) || 1;

            rows.forEach((r, i) => {
                const page = Math.floor(i / pageSize) + 1;
                r.style.display = page === currentPage ? '' : 'none';
            });

            pager.innerHTML = '';
            if (totalPages <= 1) return;

            const info = document.createElement('span');
            info.className = 'text-muted me-2';
            info.style.fontSize = '.8rem';
            info.textContent = `Trang ${currentPage}/${totalPages} (${total} dòng)`;
            pager.appendChild(info);

            for (let p = 1; p <= totalPages; p++) {
                const btn = document.createElement('button');
                btn.className = `fz-btn fz-btn--sm ${p === currentPage ? 'fz-btn--primary' : 'fz-btn--secondary'}`;
                btn.textContent = p;
                btn.addEventListener('click', () => { currentPage = p; render(); });
                pager.appendChild(btn);
            }
        }

        render();
        return { refresh: render };
    }

    return { makeSortable, paginate };
})();
