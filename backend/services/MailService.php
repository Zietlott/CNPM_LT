<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    /**
     * Gửi email chứa liên kết đặt lại mật khẩu.
     */
    public function sendPasswordResetEmail(
        string $email,
        string $resetToken
    ): bool {
        $mail = new PHPMailer(true);

        try {
            $smtpHost = getenv('SMTP_HOST') ?: 'mailpit';
            $smtpPort = (int) (getenv('SMTP_PORT') ?: 1025);
            $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'no-reply@fitzone.local';
            $fromName = getenv('SMTP_FROM_NAME') ?: 'FitZone';
            $appUrl = rtrim(
                getenv('APP_URL') ?: 'http://localhost:8080',
                '/'
            );

            $resetUrl = $appUrl
                . '/frontend/pages/auth/reset-password.php?token='
                . urlencode($resetToken);

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';

            // Mailpit không yêu cầu xác thực SMTP.
            // Khi triển khai production sẽ cấu hình SMTP thật.
            $smtpUser = getenv('SMTP_USERNAME') ?: '';
            $smtpPassword = getenv('SMTP_PASSWORD') ?: '';

            if ($smtpUser !== '' && $smtpPassword !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPassword;

                // SMTP thật cần mã hóa kết nối.
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAuth = false;
                $mail->SMTPSecure = '';
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'FitZone - Đặt lại mật khẩu';

            $safeUrl = htmlspecialchars(
                $resetUrl,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $mail->Body = '
                <h2>Khôi phục mật khẩu FitZone</h2>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu của bạn.</p>
                <p>
                    <a href="' . $safeUrl . '">
                        Nhấn vào đây để đặt lại mật khẩu
                    </a>
                </p>
                <p>Liên kết có hiệu lực trong 15 phút.</p>
                <p>Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>
            ';

            $mail->AltBody =
                "Khôi phục mật khẩu FitZone\n\n"
                . "Mở liên kết sau để đặt lại mật khẩu:\n"
                . $resetUrl
                . "\n\nLiên kết có hiệu lực trong 15 phút.";

            $mail->send();

            return true;

        } catch (Exception $e) {
            // Không ghi token hoặc URL chứa token vào log.
            error_log('Password reset email delivery failed.');

            return false;
        }
    }
}
