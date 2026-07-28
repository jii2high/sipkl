<?php

namespace App\Observers;

use App\Models\GuruProfile;

class GuruProfileObserver
{
    public function saved(GuruProfile $guruProfile): void
    {
        $guruProfile->user->syncRoles([$guruProfile->jabatan]);
    }
}
