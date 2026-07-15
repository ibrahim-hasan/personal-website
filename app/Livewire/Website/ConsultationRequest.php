<?php

namespace App\Livewire\Website;

use App\Livewire\Forms\ConsultationRequestFormData;
use App\Mail\ConsultationRequestMail;
use App\Support\SiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;
use Throwable;

class ConsultationRequest extends Component
{
    public ConsultationRequestFormData $form;

    public bool $submitted = false;

    public string $errorMessage = '';

    public function updated(string $property): void
    {
        if (! Str::startsWith($property, 'form.') || $property === 'form.website') {
            return;
        }

        $this->form->validateOnly(Str::after($property, 'form.'));
    }

    public function submit(): void
    {
        $this->errorMessage = '';

        if (filled($this->form->website)) {
            $this->submitted = true;

            return;
        }

        $payload = $this->form->validate();
        $service = collect(SiteContent::services())->firstWhere('id', $payload['service']);
        $payload['service_label'] = $service['name'] ?? $payload['service'];
        $payload['locale'] = current_locale();

        $rateLimitKey = 'consultation-request:'.Str::lower($payload['email']).'|'.request()->ip();

        try {
            $sent = RateLimiter::attempt(
                $rateLimitKey,
                3,
                function () use ($payload): bool {
                    Mail::to(SiteContent::contact()['email'])
                        ->send(new ConsultationRequestMail($payload));

                    return true;
                },
                3600,
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = __('site.consultation.error');

            return;
        }

        if ($sent === false) {
            $this->errorMessage = __('site.consultation.rate_limited');

            return;
        }

        $this->form->reset();
        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.website.consultation-request', [
            'services' => SiteContent::services(),
        ]);
    }
}
