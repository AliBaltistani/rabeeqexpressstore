<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otpCode,
        public int $expiryMinutes = 5,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Verification Code — ' . config('app.name', 'Raqeeb Express Store '),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $appName = config('app.name', 'Raqeeb Express Store ');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
        <body style="margin:0;padding:0;background:#f5f5f5;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 20px;">
                <tr><td align="center">
                    <table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                        <tr><td style="padding:32px 40px 24px;text-align:center;">
                            <h1 style="margin:0 0 8px;font-size:22px;color:#1a1a1a;">{$appName}</h1>
                            <p style="margin:0;font-size:15px;color:#666;">Your verification code is:</p>
                        </td></tr>
                        <tr><td style="padding:0 40px 24px;text-align:center;">
                            <div style="display:inline-block;padding:16px 40px;background:#f8f9fa;border-radius:8px;border:2px dashed #dee2e6;">
                                <span style="font-size:36px;font-weight:700;letter-spacing:12px;color:#1a1a1a;font-family:monospace;">{$this->otpCode}</span>
                            </div>
                        </td></tr>
                        <tr><td style="padding:0 40px 32px;text-align:center;">
                            <p style="margin:0;font-size:13px;color:#999;">This code expires in <strong>{$this->expiryMinutes} minutes</strong>.</p>
                            <p style="margin:8px 0 0;font-size:13px;color:#999;">If you didn't request this, please ignore this email.</p>
                        </td></tr>
                    </table>
                </td></tr>
            </table>
        </body>
        </html>
        HTML;
    }
}
