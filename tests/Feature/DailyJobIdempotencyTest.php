<?php

use App\Jobs\Concerns\RunsOncePerJakartaDay;
use App\Support\JakartaTime;

test('job harian hanya berjalan sekali per tanggal Jakarta', function () {
    $job = new class
    {
        use RunsOncePerJakartaDay;
    };

    expect($job->jakartaDayKey())->toContain(JakartaTime::dateKey())
        ->and($job->shouldRunToday())->toBeTrue()
        ->and($job->alreadyRanToday())->toBeTrue()
        ->and($job->shouldRunToday())->toBeFalse()
        ->and($job->shouldRunToday('perusahaan-7'))->toBeTrue();
});
