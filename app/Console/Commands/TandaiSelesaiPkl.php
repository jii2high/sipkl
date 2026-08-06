<?php

namespace App\Console\Commands;

use App\Models\PengajuanPkl;
use App\Models\PengajuanPklHistory;
use Illuminate\Console\Command;

class TandaiSelesaiPkl extends Command
{
    protected $signature = 'pkl:tandai-selesai';

    protected $description = 'Tandai pengajuan resmi_pkl menjadi selesai_pkl bila periode berakhir, laporan akhir diunggah, dan formulir penilaian sudah disubmit (BR13)';

    public function handle(): void
    {
        $count = 0;

        PengajuanPkl::where('status', 'resmi_pkl')
            ->whereNotNull('laporan_akhir_uploaded_at')
            ->whereHas('formulirPenilaian', fn ($q) => $q->whereNotNull('submitted_at'))
            ->whereHas('periode', fn ($q) => $q->where('tanggal_selesai', '<', now()))
            ->each(function (PengajuanPkl $pengajuan) use (&$count) {
                $pengajuan->update(['status' => 'selesai_pkl']);

                // BR6: tiap perubahan status dicatat di history.
                PengajuanPklHistory::create([
                    'pengajuan_id' => $pengajuan->id,
                    'actor_id' => $pengajuan->siswa->user_id,
                    'status_dari' => 'resmi_pkl',
                    'status_ke' => 'selesai_pkl',
                ]);

                $count++;
            });

        $this->info("{$count} pengajuan ditandai selesai.");
    }
}
