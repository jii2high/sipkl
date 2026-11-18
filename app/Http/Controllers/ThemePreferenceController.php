<?php

namespace App\Http\Controllers;

use App\Enums\Theme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Simpan preferensi tema per akun (DESIGN.md "Perilaku tema"). Dipanggil oleh
 * pemilih tema di halaman berakun; tanpa akun, tema tetap disimpan di perangkat.
 */
class ThemePreferenceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'theme' => ['required', Rule::enum(Theme::class)],
        ]);

        $request->user()->forceFill(['theme_preference' => $data['theme']])->save();

        return response()->noContent();
    }
}
