<?php

namespace App\Actions\Master;

use App\Actions\Action;
use App\Enums\Role;
use App\Models\SignatureAsset;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AuditService;
use App\Services\FileStorageService;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Unggah scan TTD Kepsek (BR-F2-5, Q-G). Hanya satu scan aktif: unggahan baru
 * menonaktifkan scan lama milik pemilik yang sama dalam transaksi yang sama,
 * disimpan pada disk privat (bukan publik). Kepsek adalah pemilik tanda tangan;
 * Hubin adalah pengunggah. Validasi jenis dan ukuran berkas memakai
 * `FileStorageService` (MIME dari isi berkas, nama acak).
 */
final class UploadKepsekSignature extends Action
{
    public function handle(mixed ...$arguments): SignatureAsset
    {
        [$file, $uploadedBy] = $arguments + [null, null];

        if (! $file instanceof UploadedFile || ! $uploadedBy instanceof User) {
            throw new RuntimeException('Berkas dan pengunggah wajib diisi.');
        }

        $kepsek = $this->kepsek();

        return $this->transaction(function () use ($file, $uploadedBy, $kepsek): SignatureAsset {
            SignatureAsset::query()
                ->where('owner_user_id', $kepsek->getKey())
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $path = app(FileStorageService::class)->store($file, 'signature');

            $asset = SignatureAsset::query()->create([
                'owner_user_id' => $kepsek->getKey(),
                'file_path' => $path,
                'is_active' => true,
                'uploaded_by' => $uploadedBy->getKey(),
            ]);

            app(AuditService::class)->log(
                description: 'Scan TTD Kepsek diperbarui',
                subject: $asset,
                event: 'signature_uploaded',
                properties: ['kepsek_id' => $kepsek->getKey(), 'path' => $path],
                causer: $uploadedBy,
            );

            return $asset;
        });
    }

    /** Pemilik TTD: akun pertama dengan peran Kepsek. */
    public function kepsek(): User
    {
        $ownerId = UserRole::query()->where('role', Role::Kepsek->value)->value('user_id');
        $kepsek = $ownerId !== null ? User::query()->find($ownerId) : null;

        if ($kepsek === null) {
            throw new RuntimeException('Belum ada akun Kepala Sekolah; hubungi pemasang sistem.');
        }

        return $kepsek;
    }
}
