<?php

namespace App\Livewire\Website;

use App\Actions\Consultation\ConsultationRequestRules;
use App\Actions\Consultation\ConsultationSubmissionToken;
use App\Actions\Consultation\SubmitConsultationRequest;
use App\Livewire\Forms\ConsultationRequestFormData;
use App\Support\SiteContent;
use App\Support\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class ConsultationRequest extends Component
{
    public ConsultationRequestFormData $form;

    public bool $submitted = false;

    public bool $analyticsSuccess = false;

    public string $errorMessage = '';

    public string $analyticsErrorCategory = '';

    public string $publicReference = '';

    public string $submissionToken = '';

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
        $this->analyticsErrorCategory = $this->standardAnalyticsErrorCategory();
        $this->submissionToken = app(ConsultationSubmissionToken::class)->current();

        $submitted = session()->pull('consultation.submitted');

        if (is_array($submitted)) {
            $this->submitted = true;
            $this->analyticsSuccess = (bool) ($submitted['analytics_success'] ?? false);
            $this->publicReference = (string) ($submitted['public_reference'] ?? '');

            return;
        }

        $this->fillOldInput();

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

    public function submit(Turnstile $turnstile, SubmitConsultationRequest $submit): void
    {
        $this->errorMessage = '';
        $this->analyticsErrorCategory = '';

        if (filled($this->form->website)) {
            $this->submitted = true;

            return;
        }

        if ($turnstile->enabled()
            && ! $turnstile->verify($this->turnstileToken, $turnstile->clientIp(request()))) {
            $this->errorMessage = __('validation.turnstile');
            $this->analyticsErrorCategory = 'turnstile';
            $this->dispatch('reset-consultation-turnstile');
            $this->dispatch('consultation-submit-error', category: 'turnstile');

            return;
        }

        try {
            $payload = $this->form->validate();
            $result = $submit->handle(
                $payload,
                $this->submissionToken,
                request()->ip(),
            );
        } catch (ValidationException $exception) {
            $this->analyticsErrorCategory = 'validation';
            $this->dispatch('consultation-submit-error', category: 'validation');

            throw $exception;
        } catch (Throwable) {
            Log::warning('Consultation request could not be stored.', [
                'channel' => 'consultation',
            ]);

            $this->errorMessage = __('site.consultation.error');
            $this->analyticsErrorCategory = 'unknown';
            $this->dispatch('consultation-submit-error', category: 'unknown');

            return;
        }

        if ($result === null) {
            $this->errorMessage = __('site.consultation.rate_limited');
            $this->analyticsErrorCategory = 'rate_limited';
            $this->dispatch('consultation-submit-error', category: 'rate_limited');

            return;
        }

        $this->form->reset();
        $this->publicReference = (string) $result->inquiry->public_reference;
        $this->submitted = true;
        $this->analyticsSuccess = true;
        $this->dispatch('consultation-submitted');
    }

    public function render(): View
    {
        return view('livewire.website.consultation-request', [
            'services' => $this->availableServices(),
            'channels' => SiteContent::contact()['channels'],
        ]);
    }

    /** @return list<array{key: string, id: string, name: string}> */
    private function availableServices(): array
    {
        return app(ConsultationRequestRules::class)->availableServices();
    }

    private function fillOldInput(): void
    {
        $oldInput = session()->getOldInput();

        if (! is_array($oldInput)) {
            return;
        }

        foreach (['name', 'email', 'company', 'role', 'service', 'challenge', 'timing'] as $field) {
            $value = $oldInput[$field] ?? '';

            if (is_string($value)) {
                $this->form->{$field} = $value;
            }
        }
    }

    private function standardAnalyticsErrorCategory(): string
    {
        $category = session('consultation.analytics_error');

        if (in_array($category, ['validation', 'turnstile', 'rate_limited', 'unknown'], true)) {
            return $category;
        }

        $errors = session()->get('errors');

        if (! $errors instanceof ViewErrorBag) {
            return '';
        }

        $defaultBag = $errors->getBag('default');

        if ($defaultBag->isEmpty()) {
            return '';
        }

        return $defaultBag->has('cf-turnstile-response') ? 'turnstile' : 'validation';
    }
}
