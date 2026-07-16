<?php

namespace App\Livewire\Website;

use App\Livewire\Forms\ConsultationRequestFormData;
use App\Mail\ConsultationRequestMail;
use App\Models\ContactInquiry;
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
        $service = collect($this->availableServices())->firstWhere('id', $payload['service']);
        $payload['service_label'] = $service['name'] ?? __('site.consultation.general_service');
        $payload['locale'] = current_locale();

        $rateLimitKey = 'consultation-request:'.Str::lower($payload['email']).'|'.request()->ip();

        try {
            $sent = RateLimiter::attempt(
                $rateLimitKey,
                3,
                function () use ($payload): bool {
                    ContactInquiry::query()->create([
                        'name' => $payload['name'],
                        'email' => $payload['email'],
                        'company' => $payload['company'],
                        'service_key' => $payload['service'],
                        'service_label' => $payload['service_label'],
                        'challenge' => $payload['challenge'],
                        'locale' => $payload['locale'],
                        'received_at' => now(),
                    ]);

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

        try {
            Mail::to(SiteContent::contact()['email'])
                ->queue(new ConsultationRequestMail($payload));
        } catch (Throwable $exception) {
            report($exception);
        }

        $this->form->reset();
        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.website.consultation-request', [
            'services' => $this->availableServices(),
        ]);
    }

    /** @return list<array{id: string, name: string}> */
    private function availableServices(): array
    {
        return [
            ...SiteContent::services(),
            [
                'id' => 'general',
                'name' => __('site.consultation.general_service'),
            ],
        ];
    }
}
