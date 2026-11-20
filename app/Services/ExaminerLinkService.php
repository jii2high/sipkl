<?php

namespace App\Services;

use App\Models\ExternalExaminer;
use App\Notifications\ExaminerLinkNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Pengiriman tautan portal penguji (F9 alur 2). Email dikirim lewat notifikasi;
 * kontak WhatsApp tidak dapat dikirim server, jadi Kaprog diberi tautan `wa.me`
 * siap kirim (TRD.md §9).
 */
final class ExaminerLinkService
{
    public function url(string $token): string
    {
        return route('eksternal.penguji', ['token' => $token]);
    }

    /** Kirim lewat email bila kontaknya email; selalu kembalikan tautan wa.me. */
    public function deliver(ExternalExaminer $examiner, string $url, string $pin): string
    {
        $contact = trim((string) $examiner->contact_value);

        if ($examiner->contact_type === 'email' && $contact !== '') {
            Notification::route('mail', $contact)->notify(new ExaminerLinkNotification($url, $pin));
        }

        $text = "Tautan penilaian sidang SIM PKL: {$url}\nPIN Anda: {$pin}\nJangan bagikan tautan atau PIN ini.";

        return 'https://wa.me/'.$this->waNumber($contact).'?text='.rawurlencode($text);
    }

    private function waNumber(string $value): string
    {
        $digits = (string) preg_replace('/\D+/', '', $value);

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }
}
