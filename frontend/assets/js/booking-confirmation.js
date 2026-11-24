const confirmBookingAction = (() => {
    const dialog = document.getElementById("bookingConfirmation");
    const form = dialog.querySelector("form");
    const title = dialog.querySelector("h2");
    const message = document.getElementById("bookingConfirmationMessage");
    const reasonField = document.getElementById("bookingConfirmationReasonField");
    const reason = document.getElementById("bookingConfirmationReason");
    const submit = form.querySelector('button[type="submit"]');

    reason.addEventListener("input", () => reason.setCustomValidity(""));
    dialog.querySelectorAll("[data-dismiss-confirmation]").forEach((button) => {
        button.addEventListener("click", () => dialog.close());
    });
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        if (reason.required && !reason.value.trim()) {
            reason.setCustomValidity("Vui lòng nhập lý do.");
            reason.reportValidity();
            return;
        }
        dialog.close("confirmed");
    });

    return function ({ heading, detail, action, requireReason = false, danger = false }) {
        if (dialog.open) return Promise.resolve(null);
        title.textContent = heading;
        message.textContent = detail;
        submit.textContent = action;
        submit.classList.toggle("booking-dialog-danger", danger);
        reason.value = "";
        reason.setCustomValidity("");
        reasonField.hidden = !requireReason;
        reason.disabled = !requireReason;
        reason.required = requireReason;
        dialog.returnValue = "";

        return new Promise((resolve) => {
            dialog.addEventListener("close", () => {
                resolve(dialog.returnValue === "confirmed" ? reason.value.trim() : null);
            }, { once: true });
            dialog.showModal();
        });
    };
})();
