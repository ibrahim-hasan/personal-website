<?php

namespace App\Livewire\Website;

use App\Livewire\Forms\NewsletterFormData;
use App\Mail\NewsletterWelcomeMail;
use App\Models\Newsletter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class NewsletterSubscribe extends Component
{
    public NewsletterFormData $form;

    public bool $subscribed = false;

    public string $errorMessage = '';

    public function subscribe(): void
    {
        $this->errorMessage = '';
        $this->form->validate();

        $existing = Newsletter::where('email', $this->form->email)->first();

        if ($existing) {
            $this->errorMessage = __('newsletter.already_subscribed');

            return;
        }

        $newsletter = Newsletter::create([
            'email' => $this->form->email,
            'is_disabled' => false,
            'unsubscribe_token' => Str::random(64),
        ]);

        Mail::to($newsletter)->send(new NewsletterWelcomeMail($newsletter));

        $this->form->reset();
        $this->subscribed = true;
    }

    public function render(): View
    {
        return view('livewire.website.newsletter-subscribe');
    }
}
