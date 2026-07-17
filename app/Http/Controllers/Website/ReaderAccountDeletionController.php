<?php

namespace App\Http\Controllers\Website;

use App\Actions\Readers\DeleteReaderAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\DeleteReaderAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReaderAccountDeletionController extends Controller
{
    public function __invoke(
        DeleteReaderAccountRequest $request,
        DeleteReaderAccount $deleteReaderAccount,
    ): RedirectResponse {
        /** @var User $reader */
        $reader = $request->user();
        $redirectUrl = localized_route('home');
        $status = __('reader_auth.account_deleted');

        $deleteReaderAccount->handle($reader);

        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->to($redirectUrl)
            ->with('status', $status);
    }
}
