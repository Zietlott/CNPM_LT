function renderChangeHistory(history) {
    if (!history?.length) return "";
    const escapeValue = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[char]);
    const labels = { approved: "Đã duyệt", rejected: "Đã từ chối", withdrawn: "Đã rút" };
    const items = history.map((request) => {
        const date = request.work_date.split("-").reverse().join("/");
        const time = request.start_time.slice(0, 5) + "–" + request.end_time.slice(0, 5);
        const reason = request.decision_reason ? "<br>" + escapeValue(request.decision_reason) : "";
        return "<li><strong>" + escapeValue(labels[request.status]) + "</strong> · " +
            escapeValue(date + " · " + time) + reason + "</li>";
    });
    return '<details class="booking-change-pending"><summary>Lịch sử đổi lịch</summary><ul>' +
        items.join("") + "</ul></details>";
}
