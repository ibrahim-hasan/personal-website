<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\UpdateReaderAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReaderAccountController extends Controller
{
    public function show(Request $request): View
    {
        $reader = $request->user();

        abort_unless($reader instanceof User, 403);

        return view('website.reader-account', [
            'reader' => $reader,
            'stats' => [
                'bookmarks' => $reader->articleBookmarks()->count(),
                'progress' => $reader->articleReadingProgresses()->count(),
                'comments' => $reader->comments()->count(),
            ],
            'canDeleteAccount' => $reader->isReaderAccount()
                && ! $reader->canAccessPanel(filament()->getPanel('admin')),
        ]);
    }

    public function update(UpdateReaderAccountRequest $request): RedirectResponse
    {
        /** @var User $reader */
        $reader = $request->user();
        $email = $request->string('email')->toString();
        $emailChanged = Str::lower($reader->email) !== Str::lower($email);

        $reader->fill([
            'name' => $request->string('name')->toString(),
            'email' => $email,
        ]);

        if ($emailChanged) {
            $reader->email_verified_at = null;
        }

        $reader->save();

        if ($emailChanged) {
            $reader->sendEmailVerificationNotification();

            return redirect()
                ->to(localized_route('reader.verification.notice'))
                ->with('status', __('reader_auth.email_changed_verify'));
        }

        return redirect()
            ->to(localized_route('reader.account'))
            ->with('status', __('reader_auth.profile_updated'));
    }
}
