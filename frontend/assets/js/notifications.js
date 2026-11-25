/**
 * FITZONE - Global Notifications Handler
 */
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnNotification');
    const badge = document.getElementById('notifBadge');
    const dropdown = document.getElementById('notifDropdown');
    const list = document.getElementById('notifList');
    const btnMarkAll = document.getElementById('btnMarkAllRead');

    if (!btn || !dropdown) return;

    let notificationsData = [];

    // Toggle dropdown
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = dropdown.style.display === 'block';
        dropdown.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) {
            fetchNotifications();
        }
    });

    // Close dropdown on click outside
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Mark all as read
    if (btnMarkAll) {
        btnMarkAll.addEventListener('click', async function (e) {
            e.stopPropagation();
            try {
                const res = await fetch('../../backend/api/notification/read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ all: true })
                });
                const json = await res.json();
                if (json.success) {
                    badge.style.display = 'none';
                    badge.textContent = '0';
                    fetchNotifications();
                }
            } catch (err) {
                console.error('Mark read error:', err);
            }
        });
    }

    // Fetch notifications
    async function fetchNotifications() {
        try {
            const res = await fetch('../../backend/api/notification/list.php');
            if (!res.ok) return;
            const payload = await res.json();
            const data = payload.data || payload;

            const notifs = data.notifications || [];
            const unread = data.unread_count || 0;

            if (unread > 0) {
                badge.textContent = unread > 99 ? '99+' : unread;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }

            renderNotifications(notifs);
        } catch (err) {
            console.error('Fetch notifications error:', err);
        }
    }

    function renderNotifications(notifs) {
        if (!list) return;
        if (!notifs || notifs.length === 0) {
            list.innerHTML = '<div style="text-align:center;padding:24px 8px;color:#94a3b8;font-size:0.875rem;">Không có thông báo nào</div>';
            return;
        }

        list.innerHTML = notifs.map(n => {
            const isUnread = !n.is_read || n.is_read == 0;
            const bg = isUnread ? '#f0fdf4' : '#ffffff';
            const border = isUnread ? '#bbf7d0' : '#f1f5f9';
            const icon = n.type === 'Booking' ? '📅' : (n.type === 'Payment' ? '💳' : '🔔');
            const timeStr = formatNotifTime(n.created_at);

            return `
                <div class="notif-item" data-id="${n.id}" style="padding:10px 12px;border-radius:8px;background:${bg};border:1px solid ${border};cursor:pointer;transition:background 0.15s ease;"
                     onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='${bg}'">
                    <div style="display:flex;gap:10px;align-items:flex-start;">
                        <span style="font-size:1.1rem;margin-top:2px;">${icon}</span>
                        <div style="flex:1;">
                            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:6px;">
                                <strong style="font-size:0.85rem;color:#0f172a;">${escapeHtml(n.title)}</strong>
                                <span style="font-size:0.75rem;color:#94a3b8;white-space:nowrap;">${timeStr}</span>
                            </div>
                            <p style="margin:4px 0 0;font-size:0.8rem;color:#475569;line-height:1.4;">${escapeHtml(n.content)}</p>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Item click to mark single read
        list.querySelectorAll('.notif-item').forEach(el => {
            el.addEventListener('click', async function () {
                const id = this.getAttribute('data-id');
                try {
                    await fetch('../../backend/api/notification/read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: parseInt(id) })
                    });
                } catch (e) {}
                fetchNotifications();
            });
        });
    }

    function formatNotifTime(dt) {
        if (!dt) return '';
        const d = new Date(dt.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dt;
        const now = new Date();
        const diffMin = Math.floor((now - d) / 60000);
        if (diffMin < 1) return 'Vừa xong';
        if (diffMin < 60) return `${diffMin} phút trước`;
        const diffHours = Math.floor(diffMin / 60);
        if (diffHours < 24) return `${diffHours} giờ trước`;
        return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}`;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Initial check
    fetchNotifications();
    // Poll every 45s
    setInterval(fetchNotifications, 45000);
});
