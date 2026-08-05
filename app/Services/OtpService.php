<?php

namespace App\Services;

use App\Mail\OtpPasswordChangeMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    private const TTL_MINUTES = 10;

    public function generate(User $user): void
    {
        // hapus kode lama yang belum dipakai, jangan ada 2 kode aktif
        OtpCode::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->delete();

        // generate 6 digit acak dengan nol di depan (mis. "000123")
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code), // simpan hash, bukan plaintext
            'expires_at' => now()->addMinutes(self::TTL_MINUTES), // berlaku 10 menit
        ]);

        // kirim kode ke email user
        Mail::to($user->email)->send(new OtpPasswordChangeMail($code));
    }

    // return true kalau valid, false kalau salah/expired/max attempt
    public function verify(User $user, string $inputCode): bool
    {
        // cari kode aktif terbaru milik user
        $otp = OtpCode::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        // tolak kalau expired / sudah 5x salah
        if (! $otp || $otp->isExpired() || $otp->isMaxAttempts()) {
            return false;
        }

        if (! Hash::check($inputCode, $otp->code_hash)) {
            $otp->increment('attempts'); // naikkan hitungan percobaan salah

            return false;
        }

        $otp->update(['verified_at' => now()]); // tandai kode terpakai

        return true;
    }
}
