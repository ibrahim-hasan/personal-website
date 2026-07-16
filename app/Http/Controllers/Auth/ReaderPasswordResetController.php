<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ReaderForgotPasswordRequest;
use App\Http\Requests\Auth\ReaderResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReaderPasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.reader-forgot-password');
    }

    public function store(ReaderForgotPasswordRequest $request): RedirectResponse
    {
        Password::broker()->sendResetLink([
            'email' => $request->string('email')->toString(),
        ]);

        return back()->with('status', __('reader_auth.reset_link_sent'));
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reader-reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function update(ReaderResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker()->reset(
            $request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PasswordReset) {
            return redirect()
                ->to(localized_route('reader.login'))
                ->with('status', __('reader_auth.password_reset_success'));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
