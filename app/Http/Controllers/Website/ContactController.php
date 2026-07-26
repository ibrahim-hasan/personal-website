<?php

namespace App\Http\Controllers\Website;

use App\Actions\Consultation\SubmitConsultationRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsultationRequest;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('website.contact', [
            'contact' => SiteContent::contact(),
            'services' => SiteContent::services(),
        ]);
    }

    public function store(StoreConsultationRequest $request, SubmitConsultationRequest $submit): RedirectResponse
    {
        if (filled($request->string('website')->toString())) {
            return $this->redirectToContact(analyticsSuccess: false);
        }

        try {
            $result = $submit->handle(
                $request->consultationPayload(),
                $request->string('submission_token')->toString(),
                $request->ip(),
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->with('consultation.analytics_error', 'validation')
                ->withInput($this->safeOldInput($request));
        } catch (Throwable) {
            Log::warning('Consultation request could not be stored.', [
                'channel' => 'consultation',
            ]);

            return back()
                ->withErrors(['consultation' => __('site.consultation.error')])
                ->with('consultation.analytics_error', 'unknown')
                ->withInput($this->safeOldInput($request));
        }

        if ($result === null) {
            return back()
                ->withErrors(['consultation' => __('site.consultation.rate_limited')])
                ->with('consultation.analytics_error', 'rate_limited')
                ->withInput($this->safeOldInput($request));
        }

        return $this->redirectToContact((string) $result->inquiry->public_reference);
    }

    private function redirectToContact(?string $publicReference = null, bool $analyticsSuccess = true): RedirectResponse
    {
        return redirect()->to(localized_route('contact').'#consultation')
            ->with('consultation.submitted', [
                'analytics_success' => $analyticsSuccess,
                'public_reference' => $publicReference,
            ]);
    }

    /** @return array<string, mixed> */
    private function safeOldInput(StoreConsultationRequest $request): array
    {
        return $request->except(['website', 'submission_token', 'cf-turnstile-response']);
    }
}
