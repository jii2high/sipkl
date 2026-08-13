<?php

namespace App\Http\Controllers\Hubin;

use App\Exports\TemplateGuruExport;
use App\Http\Controllers\Controller;
use App\Imports\GuruImport;
use App\Models\GuruProfile;
use App\Models\KompetensiKeahlian;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KelolaStaffController extends Controller
{
    private const LABEL_JABATAN = [
        'kaprog' => 'Kaprog',
        'guru_pembimbing' => 'Guru Pembimbing',
        'hubin' => 'Staf Hubin',
    ];

    public function index(): Response
    {
        $guru = GuruProfile::with('user', 'kompetensiKeahlian')
            ->when(request('search'), fn ($q, $v) => $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$v}%")))
            ->when(request('jabatan'), fn ($q, $v) => $q->where('jabatan', $v))
            ->orderBy('jabatan')
            ->paginate(10)
            ->withQueryString();

        // Cek apakah sudah ada kaprog aktif (untuk warning di form)
        $adaKaprog = GuruProfile::where('jabatan', 'kaprog')->exists();

        return Inertia::render('hubin/kelola-staff/index', [
            'guru' => $guru,
            'jurusan' => KompetensiKeahlian::orderBy('nama')->get(),
            'adaKaprog' => $adaKaprog,
        ]);
    }

    /**
     * Tambah akun staff baru. Default password = NIP.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => 'required|string|max:30',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'jabatan' => 'required|in:kaprog,guru_pembimbing,hubin',
            'kompetensi_keahlian_id' => 'nullable|exists:kompetensi_keahlian,id',
            'password' => 'nullable|string|min:6',
            'force' => 'boolean',
        ]);

        // Duplicate check: nip, email
        $duplikat = $this->cariDuplikatStaff(
            nip: $validated['nip'],
            email: $validated['email'],
        );

        if ($duplikat && ! ($validated['force'] ?? false)) {
            return back()->with('duplikat', $duplikat);
        }

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?? $validated['nip']),
            ]);

            GuruProfile::create([
                'user_id' => $user->id,
                'nip' => $validated['nip'],
                'jabatan' => $validated['jabatan'],
                'kompetensi_keahlian_id' => $validated['kompetensi_keahlian_id'] ?? null,
            ]);
            // Role di-sync otomatis via GuruProfileObserver::saved()
        });

        return redirect()->route('hubin.kelola-staff.index')
            ->with('success', 'Akun staff berhasil ditambahkan.');
    }

    /**
     * Update data staff. Password hanya diupdate jika diisi.
     */
    public function update(Request $request, GuruProfile $guruProfile): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'nip' => 'required|string|max:30',
            'jabatan' => 'required|in:kaprog,guru_pembimbing,hubin',
            'kompetensi_keahlian_id' => 'nullable|exists:kompetensi_keahlian,id',
            'password' => 'nullable|string|min:6',
            'force' => 'boolean',
        ]);

        // Duplicate check (kecualikan user yang sedang diedit)
        $duplikat = $this->cariDuplikatStaff(
            nip: $validated['nip'],
            email: $validated['email'],
            exceptUserId: $guruProfile->user_id,
        );

        if ($duplikat && ! ($validated['force'] ?? false)) {
            return back()->with('duplikat', $duplikat);
        }

        DB::transaction(function () use ($guruProfile, $validated) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $guruProfile->user->update($userData);

            $guruProfile->update([
                'nip' => $validated['nip'],
                'jabatan' => $validated['jabatan'],
                'kompetensi_keahlian_id' => $validated['kompetensi_keahlian_id'] ?? null,
            ]);
            // Role di-sync otomatis via GuruProfileObserver::saved()
        });

        return redirect()->route('hubin.kelola-staff.index')
            ->with('success', 'Data staff berhasil diperbarui.');
    }

    /**
     * Soft delete staff (masuk recycle bin).
     */
    public function destroy(GuruProfile $guruProfile): RedirectResponse
    {
        $guruProfile->user->delete();

        return redirect()->route('hubin.recycle-bin.index')
            ->with('success', 'Staff dihapus dan masuk recycle bin.');
    }

    public function nonaktifkanKaprog(GuruProfile $guruProfile): RedirectResponse
    {
        $guruProfile->update([
            'jabatan' => 'guru_pembimbing',
            'kompetensi_keahlian_id' => null,
        ]);

        return back()->with('success', 'Kaprog dinonaktifkan — role otomatis jadi Guru Pembimbing (BR31).');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        $import = new GuruImport;
        Excel::import($import, $request->file('file'));

        if ($import->errors) {
            return back()->with('importErrors', $import->errors);
        }

        return back()->with('success', 'Import guru berhasil.');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new TemplateGuruExport, 'template_guru.xlsx');
    }

    /**
     * Cari staff yang punya nip/email sama (untuk duplicate check).
     *
     * @return array{nama: string, nip: string}|null
     */
    private function cariDuplikatStaff(string $nip, string $email, ?int $exceptUserId = null): ?array
    {
        $query = GuruProfile::with('user')
            ->where(function ($q) use ($nip, $email) {
                $q->where('nip', $nip)
                    ->orWhereHas('user', fn ($q) => $q->where('email', $email));
            });

        if ($exceptUserId) {
            $query->where('user_id', '!=', $exceptUserId);
        }

        $duplikat = $query->first();

        if (! $duplikat) {
            return null;
        }

        return [
            'nama' => $duplikat->user->name,
            'nip' => $duplikat->nip ?? '-',
        ];
    }
}
