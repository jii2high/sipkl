<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => fn () => $request->user(),
                'roles' => fn () => $request->user()?->getRoleNames()?->toArray() ?? [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'notifikasi_belum_dibaca' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            'notifikasi_terbaru' => fn () => $request->user()?->notifications()->latest()->take(5)->get() ?? [],
            // flash status (mis. pesan "kode terkirim")
            'status' => fn () => $request->session()->get('status'),
            // status verifikasi OTP untuk frontend
            'password_otp_verified' => fn () => $request->session()->has('password_otp_verified_at')
                && now()->subMinutes(10)->lt($request->session()->get('password_otp_verified_at')),
        ];
    }
}
