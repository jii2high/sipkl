<?php

namespace App\Http\Controllers\Kaprog;

use App\Http\Controllers\Controller;
use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\User;
use App\Notifications\RequestSuratTugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class SuratTugasRequestController extends Controller
{
    /**
     * Kaprog meminta Surat Tugas Scoped ke Hubin — hanya notifikasi, keputusan terbit tetap di Hubin.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'periode_id' => 'required|exists:periode_pkls,id',
            'kompetensi_keahlian_id' => 'required|exists:kompetensi_keahlian,id',
            'catatan' => 'nullable|string|max:500',
        ]);

        $periode = PeriodePkl::whereKey($validated['periode_id'])->firstOrFail();
        $kompetensiKeahlian = KompetensiKeahlian::whereKey($validated['kompetensi_keahlian_id'])->firstOrFail();

        Notification::send(
            User::role('hubin')->get(),
            new RequestSuratTugas($periode, $kompetensiKeahlian, $request->user()->name, $validated['catatan'] ?? null)
        );

        return back()->with('sukses', 'Permintaan Surat Tugas dikirim ke Hubin.');
    }
}
