<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;
    public string $recipientName;
    public string $accountType;
    public int $expiresInMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(string $resetUrl, string $recipientName, string $accountType = 'customer', int $expiresInMinutes = 60)
    {
        $this->resetUrl = $resetUrl;
        $this->recipientName = $recipientName;
        $this->accountType = $accountType;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu đặt lại mật khẩu tài khoản DriveRent',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reset_password',
        );
    }
}
