<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptCurrentTermsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReaderTermsAcceptanceController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        /** @var User $reader */
        $reader = $request->user();

        if ($reader->hasAcceptedCurrentTerms()) {
            return redirect()->to(localized_route('reader.account'));
        }

        return view('auth.reader-terms-acceptance');
    }

    public function store(AcceptCurrentTermsRequest $request): RedirectResponse
    {
        /** @var User $reader */
        $reader = $request->user();

        $reader->forceFill([
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.terms_version'),
        ])->save();

        return redirect()->intended(localized_route('reader.account'));
    }
}
