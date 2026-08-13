<?php

namespace App\Http\Controllers\Hubin;

use App\Exports\AkunExport;
use App\Exports\TemplateSiswaExport;
use App\Http\Controllers\Controller;
use App\Imports\SiswaImport;
use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KelolaSiswaController extends Controller
{
    public function index(): Response
    {
        $siswa = SiswaProfile::with('user', 'kompetensiKeahlian', 'periode')
            ->when(request('search'), fn ($q, $v) => $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$v}%")))
            ->when(request('kompetensi_keahlian_id'), fn ($q, $v) => $q->where('kompetensi_keahlian_id', $v))
            ->when(request('periode_id'), fn ($q, $v) => $q->where('periode_id', $v))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('hubin/kelola-siswa/index', [
            'siswa' => $siswa,
            'jurusan' => KompetensiKeahlian::orderBy('nama')->get(),
            'periodes' => PeriodePkl::orderByDesc('id')->get(),
        ]);
    }

    /**
     * Tambah akun siswa baru. Default password = NIS.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'periode_id' => 'required|exists:periode_pkls,id',
            'kompetensi_keahlian_id' => 'required|exists:kompetensi_keahlian,id',
            'alamat' => 'nullable|string|max:500',
            'no_telp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'force' => 'boolean',
        ]);

        // Duplicate check: nis, email, no_telp
        $duplikat = $this->cariDuplikatSiswa(
            nis: $validated['nis'],
            email: $validated['email'],
            noTelp: $validated['no_telp'] ?? null,
        );

        if ($duplikat && ! ($validated['force'] ?? false)) {
            return back()->with('duplikat', $duplikat);
        }

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?? $validated['nis']),
            ]);
            $user->assignRole('siswa');

            SiswaProfile::create([
                'user_id' => $user->id,
                'nis' => $validated['nis'],
                'periode_id' => $validated['periode_id'],
                'kompetensi_keahlian_id' => $validated['kompetensi_keahlian_id'],
                'alamat' => $validated['alamat'] ?? null,
                'no_telp' => $validated['no_telp'] ?? null,
            ]);
        });

        return redirect()->route('hubin.kelola-siswa.index')
            ->with('success', 'Akun siswa berhasil ditambahkan.');
    }

    /**
     * Update data siswa. Password hanya diupdate jika diisi.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->siswaProfile !== null, 422, 'Bukan akun siswa.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'nis' => 'required|string|max:20',
            'periode_id' => 'required|exists:periode_pkls,id',
            'kompetensi_keahlian_id' => 'required|exists:kompetensi_keahlian,id',
            'alamat' => 'nullable|string|max:500',
            'no_telp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'force' => 'boolean',
        ]);

        // Duplicate check (kecualikan user yang sedang diedit)
        $duplikat = $this->cariDuplikatSiswa(
            nis: $validated['nis'],
            email: $validated['email'],
            noTelp: $validated['no_telp'] ?? null,
            exceptUserId: $user->id,
        );

        if ($duplikat && ! ($validated['force'] ?? false)) {
            return back()->with('duplikat', $duplikat);
        }

        DB::transaction(function () use ($user, $validated) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $user->update($userData);

            $user->siswaProfile->update([
                'nis' => $validated['nis'],
                'periode_id' => $validated['periode_id'],
                'kompetensi_keahlian_id' => $validated['kompetensi_keahlian_id'],
                'alamat' => $validated['alamat'] ?? null,
                'no_telp' => $validated['no_telp'] ?? null,
            ]);
        });

        return redirect()->route('hubin.kelola-siswa.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->siswaProfile !== null, 422, 'Bukan akun siswa.');

        $user->delete();

        return redirect()->route('hubin.recycle-bin.index')
            ->with('success', 'Siswa dihapus dan masuk recycle bin.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        $import = new SiswaImport;
        Excel::import($import, $request->file('file'));

        if ($import->errors) {
            return back()->with('importErrors', $import->errors);
        }

        return back()->with('success', 'Import siswa berhasil.');
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new AkunExport, 'akun_siswa.xlsx');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new TemplateSiswaExport, 'template_siswa.xlsx');
    }

    /**
     * Cari siswa yang punya nis/email/no_telp sama (untuk duplicate check).
     *
     * @return array{nama: string, nis: string}|null
     */
    private function cariDuplikatSiswa(
        string $nis,
        string $email,
        ?string $noTelp,
        ?int $exceptUserId = null,
    ): ?array {
        $query = SiswaProfile::with('user')
            ->where(function ($q) use ($nis, $email, $noTelp) {
                $q->where('nis', $nis)
                    ->orWhereHas('user', fn ($q) => $q->where('email', $email));

                if ($noTelp) {
                    $q->orWhere('no_telp', $noTelp);
                }
            });

        if ($exceptUserId) {
            $query->whereHas('user', fn ($q) => $q->where('id', '!=', $exceptUserId));
        }

        $duplikat = $query->first();

        if (! $duplikat) {
            return null;
        }

        return [
            'nama' => $duplikat->user->name,
            'nis' => $duplikat->nis,
        ];
    }
}
