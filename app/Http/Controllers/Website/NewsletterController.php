<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterWelcomeMail;
use App\Models\Newsletter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:newsletters,email'],
        ]);

        $newsletter = Newsletter::create([
            'email' => $validated['email'],
            'is_disabled' => false,
            'unsubscribe_token' => Str::random(64),
        ]);

        Mail::to($newsletter)->send(new NewsletterWelcomeMail($newsletter));

        return back()->with('message', __('newsletter.subscribed'));
    }

    public function unsubscribe(Request $request): View|RedirectResponse
    {
        $email = $request->query('email');
        $token = $request->query('token');

        if (! $email || ! $token) {
            return redirect()
                ->to(localized_route('home'))
                ->with('error', __('newsletter.unsubscribe_invalid_link'));
        }

        $newsletter = Newsletter::where('email', $email)
            ->where('unsubscribe_token', $token)
            ->first();

        if (! $newsletter) {
            return redirect()
                ->to(localized_route('home'))
                ->with('error', __('newsletter.unsubscribe_invalid_link'));
        }

        $newsletter->update(['is_disabled' => true]);

        return view('website.newsletter-unsubscribed');
    }
}
