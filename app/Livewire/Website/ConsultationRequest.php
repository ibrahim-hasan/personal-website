<?php

namespace App\Livewire\Website;

use App\Livewire\Forms\ConsultationRequestFormData;
use App\Mail\ConsultationRequestMail;
use App\Models\ContactInquiry;
use App\Support\SiteContent;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class ConsultationRequest extends Component
{
    public ConsultationRequestFormData $form;

    public bool $submitted = false;

    public string $errorMessage = '';

    /**
     * Cloudflare Turnstile token. Populated from the widget's data-callback,
     * verified against siteverify before the consultation is stored or mailed.
     * Left empty in dev/tests when Turnstile is not configured.
     */
    public string $turnstileToken = '';

    #[On('turnstile-resolved')]
    public function setTurnstileToken(string $token = ''): void
    {
        $this->turnstileToken = $token;
    }

    public function mount(): void
    {
        $handoff = session()->pull('consultation.decision_room');

        if (! is_array($handoff)) {
            return;
        }

        $serviceKey = (string) ($handoff['service'] ?? '');
        $hasService = collect($this->availableServices())->contains(
            fn (array $service): bool => $service['key'] === $serviceKey,
        );

        if ($hasService) {
            $this->form->service = $serviceKey;
        }

        $context = str($handoff['context'] ?? '')
            ->stripTags()
            ->trim()
            ->limit(3000, '')
            ->toString();

        if ($context !== '') {
            $this->form->challenge = $context;
        }
    }

    public function updated(string $property): void
    {
        if (! Str::startsWith($property, 'form.') || $property === 'form.website') {
            return;
        }

        $this->form->validateOnly(Str::after($property, 'form.'));
    }

    public function submit(Turnstile $turnstile): void
    {
        $this->errorMessage = '';

        if (filled($this->form->website)) {
            $this->submitted = true;

            return;
        }

        if ($turnstile->enabled()
            && ! $turnstile->verify($this->turnstileToken, $turnstile->clientIp(request()))) {
            $this->errorMessage = __('validation.turnstile');
            $this->dispatch('reset-consultation-turnstile');

            return;
        }

        $payload = $this->form->validate();
        $service = collect($this->availableServices())->firstWhere('key', $payload['service']);
        $payload['service_label'] = $service['name'] ?? __('site.consultation.general_service');
        $payload['locale'] = current_locale();

        $rateLimitIdentity = Str::lower($payload['email']).'|'.request()->ip();
        $rateLimitKey = 'consultation-request:'.hash_hmac(
            'sha256',
            $rateLimitIdentity,
            (string) config('app.key'),
        );

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

    /** @return list<array{key: string, id: string, name: string}> */
    private function availableServices(): array
    {
        return [
            ...collect(SiteContent::services())
                ->map(fn (array $service): array => [
                    ...$service,
                    'key' => (string) ($service['key'] ?? $service['id']),
                ])
                ->all(),
            [
                'key' => 'general',
                'id' => 'general',
                'name' => __('site.consultation.general_service'),
            ],
        ];
    }
}
