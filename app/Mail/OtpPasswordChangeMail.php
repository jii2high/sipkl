<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpPasswordChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public string $code) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // set subject email
        return new Envelope(
            subject: 'Kode OTP Ganti Password — SI-PKL',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // definisikan view email + data kode yang dikirim
        return new Content(
            view: 'emails.otp-password-change',
            with: ['code' => $this->code],
        );
    }
}
