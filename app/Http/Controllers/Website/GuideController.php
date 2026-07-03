<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Mail\GuideDownloadMail;
use App\Models\Guide;
use App\Models\GuideDownloader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuideController extends Controller
{
    public function showForm(): View
    {
        return view('website.guide-download');
    }

    public function requestDownload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'guide_id' => ['required', 'integer', 'exists:guides,id'],
        ]);

        $guide = Guide::query()->posted()->findOrFail($validated['guide_id']);

        $existing = GuideDownloader::query()
            ->where('email', $validated['email'])
            ->where('guide_id', $guide->id)
            ->first();

        if ($existing) {
            if (! $existing->is_mail_sent) {
                $token = $existing->generateDownloadToken();
                Mail::to($existing)->send(new GuideDownloadMail($existing, $token));
                $existing->update(['is_mail_sent' => true]);
            }

            return back()->with('message', __('guide.download_link_sent'));
        }

        $downloader = GuideDownloader::create([
            'guide_id' => $guide->id,
            'email' => $validated['email'],
            'is_mail_sent' => true,
        ]);

        $token = $downloader->generateDownloadToken();
        Mail::to($downloader)->send(new GuideDownloadMail($downloader, $token));

        return back()->with('message', __('guide.download_link_sent'));
    }

    public function download(Request $request, string $token): BinaryFileResponse|RedirectResponse
    {
        $downloader = GuideDownloader::query()
            ->with('guide.media')
            ->where('download_token', $token)
            ->first();

        if (! $downloader || ! $downloader->isTokenValid($token)) {
            return redirect()
                ->to(localized_route('home'))
                ->with('error', __('guide.link_expired'));
        }

        $guide = $downloader->guide;

        if (! $guide) {
            return redirect()
                ->to(localized_route('home'))
                ->with('error', __('guide.file_unavailable'));
        }

        $media = $guide->getFirstMedia('guide_file');

        if (! $media || ! is_file($media->getPath())) {
            return redirect()
                ->to(localized_route('home'))
                ->with('error', __('guide.file_unavailable'));
        }

        $downloader->markTokenAsUsed($token);

        return response()->download($media->getPath(), $media->file_name);
    }
}
