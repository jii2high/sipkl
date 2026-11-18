<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email verifikasi signer industri (TASKS M2.8).
 *
 * URL di dalam email adalah signed URL berbatas waktu yang terikat pada email
 * signer saat dikirim; mengganti email otomatis membuat tautan lama gugur.
 */
final class SignerVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Company $company,
        public readonly string $tipe,
        public readonly string $nama,
        public readonly string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi email signer industri — SIPKL',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signer-verification',
        );
    }
}
