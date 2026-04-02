<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {

    private array $cfg;

    public function __construct() {
        $this->cfg = require __DIR__ . '/MailConfig.php';
    }

    public function sendPasswordReset(string $toEmail, string $toName, string $resetLink): bool {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $this->cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->cfg['username'];
            $mail->Password   = $this->cfg['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->cfg['port'];

            $mail->setFrom($this->cfg['from'], $this->cfg['from_name']);
            $mail->addAddress($toEmail, $toName ?: $toEmail);

            $mail->isHTML(true);
            $mail->Subject = 'NORECO 1 WMS — Password Reset';
            $mail->Body    = $this->buildHtml($toName ?: 'Admin', $resetLink);
            $mail->AltBody = "Reset your NORECO 1 WMS password here: $resetLink\n\nThis link expires in 1 hour. If you did not request this, ignore this email.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer Error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    private function buildHtml(string $name, string $link): string {
        return '
        <div style="font-family:Segoe UI,Arial,sans-serif;max-width:580px;margin:0 auto;background:#f8fafc;padding:32px 20px;border-radius:12px">
            <!-- Header -->
            <div style="text-align:center;margin-bottom:28px">
                <div style="display:inline-flex;align-items:center;justify-content:center;
                    width:52px;height:52px;border-radius:12px;
                    background:linear-gradient(135deg,#3399ff,#1f7de0)">
                    <span style="font-size:24px">&#128970;</span>
                </div>
                <h2 style="margin:12px 0 4px;font-size:20px;color:#1e2235">NORECO 1 WMS</h2>
                <p style="margin:0;font-size:12px;color:#94a3b8">Warehouse Monitoring System</p>
            </div>
            <!-- Card -->
            <div style="background:#ffffff;border-radius:12px;padding:32px;border:1px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,0.06)">
                <h3 style="margin:0 0 16px;font-size:18px;color:#1e2235">Password Reset Request</h3>
                <p style="margin:0 0 12px;font-size:14px;color:#4a5568;line-height:1.6">
                    Hi <strong>' . htmlspecialchars($name) . '</strong>,
                </p>
                <p style="margin:0 0 24px;font-size:14px;color:#4a5568;line-height:1.6">
                    We received a request to reset your password. Click the button below to set a new password.
                    This link will expire in <strong>1 hour</strong>.
                </p>
                <div style="text-align:center;margin:0 0 28px">
                    <a href="' . $link . '"
                       style="display:inline-block;padding:13px 36px;
                              background:linear-gradient(135deg,#3399ff,#1f7de0);
                              color:#ffffff;text-decoration:none;
                              border-radius:9px;font-size:14px;font-weight:700;
                              letter-spacing:0.5px;
                              box-shadow:0 6px 18px rgba(51,153,255,0.4)">
                        Reset My Password
                    </a>
                </div>
                <p style="margin:0 0 16px;font-size:13px;color:#94a3b8;line-height:1.6">
                    If you didn\'t request a password reset, you can safely ignore this email.
                    Your password will remain unchanged.
                </p>
                <hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0">
                <p style="margin:0;font-size:11px;color:#94a3b8">
                    Or paste this link in your browser:<br>
                    <a href="' . $link . '" style="color:#3399ff;word-break:break-all">' . $link . '</a>
                </p>
            </div>
            <p style="text-align:center;margin:20px 0 0;font-size:11px;color:#b0bec5">
                &copy; NORECO 1 WMS &mdash; This is an automated message, please do not reply.
            </p>
        </div>';
    }
}
