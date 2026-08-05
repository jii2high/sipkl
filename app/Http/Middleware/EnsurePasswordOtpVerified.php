<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordOtpVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ambil waktu verifikasi OTP dari session
        $verifiedAt = $request->session()->get('password_otp_verified_at');

        // masih valid kalau belum lewat 10 menit
        if ($verifiedAt && now()->subMinutes(10)->lt($verifiedAt)) {
            return $next($request);
        }

        // simpan tujuan kembali setelah verify
        $request->session()->put(
            'otp_redirect_to',
            $request->user()->must_change_password
                ? route('password.force-change')
                : route('security.edit'),
        );

        // arahkan ke halaman ganti password (menampilkan step OTP)
        return redirect()->route('password.force-change');
    }
}
