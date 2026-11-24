<dialog id="bookingConfirmation" class="booking-dialog"
        aria-labelledby="bookingConfirmationTitle" aria-describedby="bookingConfirmationMessage">
    <form>
        <div class="booking-dialog-heading">
            <h2 id="bookingConfirmationTitle"></h2>
            <button type="button" class="booking-dialog-close" data-dismiss-confirmation aria-label="Đóng">×</button>
        </div>
        <p id="bookingConfirmationMessage"></p>
        <div class="mb-3" id="bookingConfirmationReasonField" hidden>
            <label for="bookingConfirmationReason" class="form-label">Lý do</label>
            <textarea id="bookingConfirmationReason" class="form-control" rows="3" maxlength="255"></textarea>
        </div>
        <div class="booking-dialog-actions">
            <button type="button" class="booking-dialog-secondary" data-dismiss-confirmation autofocus>Quay lại</button>
            <button type="submit" class="booking-submit-button"></button>
        </div>
    </form>
</dialog>
