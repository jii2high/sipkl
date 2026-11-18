<?php

namespace App\Actions\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Gabung perusahaan duplikat ke perusahaan utama (TASKS M2.5).
 *
 * Aturan:
 * - Sumber tidak boleh sama dengan target, tidak boleh sudah di-merge, dan
 *   rantai `merged_into_id` tidak boleh melingkar.
 * - Kolom unik sumber (email/website/telp_fax) dikosongkan supaya UNIQUE di DB
 *   tidak bertabrakan dengan data target; record sumber tetap ada sebagai jejak.
 * - Referensi yang sudah ada (pengajuan, plotting, visitasi, form) dipindahkan
 *   dalam transaksi yang sama; snapshot JSON tidak disentuh.
 * - Setiap merge menulis `activity_log` tanpa data sensitif.
 */
final class MergeCompanyAction
{
    /**
     * Tabel/kolom yang menunjuk `companies.id` dan harus ikut berpindah.
     *
     * Tabel yang belum ada pada milestone berjalan dilewati otomatis
     * (`Schema::hasTable`), jadi daftar ini aman untuk M2 dan dipakai kembali
     * saat tabel terkait muncul di milestone berikutnya.
     *
     * Catatan: `pengajuan_siswa.company_id` punya FK komposit ke
     * `pengajuan_pkl(id, company_id, academic_period_id)`. Saat M3 tiba,
     * pemindahan referensi untuk pasangan tabel itu perlu penanganan khusus
     * (lepas tautan komposit -> pindahkan -> tautkan ulang); sampai saat itu
     * merge dengan data pengajuan akan gagal keras di level FK, bukan
     * menghasilkan data silang.
     *
     * @var array<string, list<string>>
     */
    public const REFERENSI_DEFAULT = [
        'companies' => ['merged_into_id'],
        'pengajuan_pkl' => ['company_id'],
        'pengajuan_siswa' => ['company_id'],
        'guru_pembimbing_plotting' => ['company_id'],
        'surat_tugas_visitasi_companies' => ['company_id'],
        'visitasi_dokumentasi' => ['company_id'],
        'form_submissions' => ['company_id'],
    ];

    /**
     * @param  array<string, list<string>>|null  $referensi  Override daftar referensi (dipakai test).
     */
    public function __construct(private readonly ?array $referensi = null) {}

    public function execute(
        Company $sumber,
        Company $target,
        ?User $oleh = null,
        ?string $alasan = null,
    ): Company {
        if ($sumber->getKey() === $target->getKey()) {
            throw ValidationException::withMessages([
                'target_id' => 'Perusahaan tidak bisa digabung ke dirinya sendiri.',
            ]);
        }

        return DB::transaction(function () use ($sumber, $target, $oleh, $alasan): Company {
            /** @var Company $sumber */
            $sumber = Company::query()->lockForUpdate()->findOrFail($sumber->getKey());
            /** @var Company $target */
            $target = Company::query()->lockForUpdate()->findOrFail($target->getKey());

            if ($sumber->getKey() === $target->getKey()) {
                throw ValidationException::withMessages([
                    'target_id' => 'Perusahaan tidak bisa digabung ke dirinya sendiri.',
                ]);
            }

            if ($sumber->merged_into_id !== null) {
                throw ValidationException::withMessages([
                    'target_id' => 'Perusahaan sumber sudah pernah digabungkan.',
                ]);
            }

            if ($target->merged_into_id !== null) {
                throw ValidationException::withMessages([
                    'target_id' => 'Perusahaan target sudah digabungkan ke perusahaan lain. Pilih perusahaan utama.',
                ]);
            }

            $this->pastikanTidakMelingkar($sumber, $target);

            $sumber->forceFill([
                'email' => null,
                'website' => null,
                'telp_fax' => null,
                'merged_into_id' => $target->getKey(),
            ])->save();

            $referensi = $this->pindahkanReferensi($sumber, $target);

            activity('company')
                ->performedOn($sumber)
                ->causedBy($oleh)
                ->withProperties([
                    'sumber_id' => $sumber->getKey(),
                    'sumber_nama' => $sumber->nama,
                    'target_id' => $target->getKey(),
                    'target_nama' => $target->nama,
                    'referensi' => $referensi,
                    'alasan' => $alasan,
                ])
                ->log('company.merged');

            return $target;
        });
    }

    private function pastikanTidakMelingkar(Company $sumber, Company $target): void
    {
        $dikunjungi = [];
        $sekarang = $target;

        while ($sekarang !== null) {
            $id = $sekarang->getKey();

            if (in_array($id, $dikunjungi, true)) {
                throw ValidationException::withMessages([
                    'target_id' => 'Rantai merge perusahaan target sudah melingkar.',
                ]);
            }

            if ($id === $sumber->getKey()) {
                throw ValidationException::withMessages([
                    'target_id' => 'Merge ini akan membentuk rantai melingkar.',
                ]);
            }

            $dikunjungi[] = $id;

            $sekarang = $sekarang->merged_into_id === null
                ? null
                : Company::query()->find($sekarang->merged_into_id);
        }
    }

    /**
     * @return array<string, array<string, int>> jumlah baris yang dipindah per tabel/kolom
     */
    private function pindahkanReferensi(Company $sumber, Company $target): array
    {
        $dipindah = [];

        foreach ($this->referensi ?? self::REFERENSI_DEFAULT as $tabel => $kolom) {
            if (! Schema::hasTable($tabel)) {
                continue;
            }

            foreach ($kolom as $namaKolom) {
                if (! Schema::hasColumn($tabel, $namaKolom)) {
                    continue;
                }

                $jumlah = DB::table($tabel)
                    ->where($namaKolom, $sumber->getKey())
                    ->update([$namaKolom => $target->getKey()]);

                if ($jumlah > 0) {
                    $dipindah[$tabel][$namaKolom] = $jumlah;
                }
            }
        }

        return $dipindah;
    }
}
