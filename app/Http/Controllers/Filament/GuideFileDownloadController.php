<?php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuideFileDownloadController extends Controller
{
    public function __invoke(Guide $guide): BinaryFileResponse|Response
    {
        Gate::authorize('view', $guide);

        $media = $guide->getFirstMedia('guide_file');

        if (! $media || ! is_file($media->getPath())) {
            abort(404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }
}
