<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ReaderRegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReaderRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.reader-register');
    }

    public function store(ReaderRegisterRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'locale_preference' => app()->getLocale(),
            'is_active' => true,
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.terms_version'),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->to(localized_route('reader.verification.notice'));
    }
}
