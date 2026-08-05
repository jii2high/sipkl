<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class PasswordOtpController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function request(Request $request): RedirectResponse
    {
        // kunci rate limit per user
        $key = 'otp-request:'.$request->user()->id;

        // batasi maks 3x request
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['otp' => 'Terlalu banyak percobaan, coba lagi nanti.']);
        }

        // catat request, cooldown 10 menit
        RateLimiter::hit($key, 600);

        // isi tujuan pulang kalau belum diset middleware
        if (! $request->session()->has('otp_redirect_to')) {
            $request->session()->put('otp_redirect_to', url()->previous());
        }

        // buat kode baru + kirim email
        $this->otp->generate($request->user());

        // flash pesan sukses ke halaman
        return back()->with('status', 'Kode OTP sudah dikirim ke email kamu.');
    }

    public function verify(Request $request): RedirectResponse
    {
        // kode wajib 6 digit angka
        $request->validate(['code' => 'required|digits:6']);

        // kode salah / expired / kehabisan percobaan
        if (! $this->otp->verify($request->user(), $request->code)) {
            return back()->withErrors(['code' => 'Kode salah atau sudah kedaluwarsa.']);
        }

        // tandai sudah verified di session
        $request->session()->put('password_otp_verified_at', now());

        // ambil & hapus tujuan kembali (sekali pakai)
        $redirectTo = $request->session()->pull('otp_redirect_to');

        return $redirectTo ? redirect($redirectTo) : redirect()->route('password.force-change');
    }
}
