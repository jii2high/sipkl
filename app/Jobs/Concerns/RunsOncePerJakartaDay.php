<?php

namespace App\Jobs\Concerns;

use App\Support\JakartaTime;
use Illuminate\Support\Facades\Cache;

/**
 * Kunci "sudah jalan untuk tanggal Jakarta X" (TRD.md §8, BR-F14-3). Job harian
 * memakai `shouldRunToday()` di awal: panggilan kedua pada tanggal Jakarta yang
 * sama tidak lagi menjalankan efek. TTL kunci berakhir di 23:59:59 Jakarta.
 */
trait RunsOncePerJakartaDay
{
    /**
     * Kunci dedup unik per kelas job, per tanggal Jakarta. `$scope` membedakan
     * beberapa target dalam satu job (mis. per pengajuan atau per perusahaan).
     */
    public function jakartaDayKey(string $scope = ''): string
    {
        $key = 'job-harian:'.static::class.':'.JakartaTime::dateKey();

        return $scope === '' ? $key : $key.':'.$scope;
    }

    /** Sudah dijalankan untuk tanggal Jakarta hari ini? */
    public function alreadyRanToday(string $scope = ''): bool
    {
        return Cache::has($this->jakartaDayKey($scope));
    }

    /**
     * Tandai job berjalan dan kembalikan `true`, atau `false` bila sudah berjalan
     * hari ini. Aman terhadap balapan proses (Cache::add bersifat atomic).
     */
    public function shouldRunToday(string $scope = ''): bool
    {
        return Cache::add(
            $this->jakartaDayKey($scope),
            JakartaTime::now()->toIso8601String(),
            JakartaTime::endOfDay(JakartaTime::now()),
        );
    }
}
