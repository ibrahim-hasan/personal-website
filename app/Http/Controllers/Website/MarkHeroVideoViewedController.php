<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class MarkHeroVideoViewedController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->hero_video_seen_at === null) {
            $user->forceFill([
                'hero_video_seen_at' => now(),
            ])->save();
        }

        return response()->noContent();
    }
}
