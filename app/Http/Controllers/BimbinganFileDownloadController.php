<?php

namespace App\Http\Controllers;

use App\Models\BimbinganSubmission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BimbinganFileDownloadController extends Controller
{
    public function __invoke(int $id): StreamedResponse
    {
        $submission = BimbinganSubmission::query()->findOrFail($id);
        Gate::authorize('download', $submission);

        $path = $submission->file_path;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, basename($path));
    }
}
