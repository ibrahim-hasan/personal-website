<?php

namespace App\Actions\Consultation;

use App\Models\ContactInquiry;
use App\Services\Consultation\ConsultationNotificationDispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SubmitConsultationRequest
{
    private const string REFERENCE_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public function __construct(
        private readonly ConsultationRequestRules $rules,
        private readonly ConsultationSubmissionToken $tokens,
        private readonly ConsultationNotificationDispatcher $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $submissionToken, ?string $remoteIp): ?ConsultationSubmissionResult
    {
        $submissionHash = $this->tokens->hash($submissionToken);
        $existing = ContactInquiry::query()
            ->where('submission_hash', $submissionHash)
            ->first();

        if ($existing !== null) {
            return new ConsultationSubmissionResult($existing, false);
        }

        if (! $this->tokens->matches($submissionToken)) {
            throw ValidationException::withMessages([
                'consultation' => [__('site.consultation.error')],
            ]);
        }

        $normalized = $this->rules->normalize($payload);
        $rateLimitKey = 'consultation-request:'.hash_hmac(
            'sha256',
            mb_strtolower($normalized['email']).'|'.($remoteIp ?? ''),
            (string) config('app.key'),
        );

        $result = RateLimiter::attempt(
            $rateLimitKey,
            3,
            fn (): ConsultationSubmissionResult => $this->createOrReuse($normalized, $submissionHash),
            3600,
        );

        if ($result === false) {
            return null;
        }

        if ($result->wasCreated) {
            $this->tokens->rotate();
            $this->notifications->dispatch($result->inquiry);
        }

        return $result;
    }

    /**
     * @param  array{name: string, email: string, company: string|null, role: string|null, service: string, challenge: string, timing: string|null}  $payload
     */
    private function createOrReuse(array $payload, string $submissionHash): ConsultationSubmissionResult
    {
        try {
            return DB::transaction(function () use ($payload, $submissionHash): ConsultationSubmissionResult {
                $existing = ContactInquiry::query()
                    ->where('submission_hash', $submissionHash)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    return new ConsultationSubmissionResult($existing, false);
                }

                return new ConsultationSubmissionResult(
                    ContactInquiry::query()->create([
                        'name' => $payload['name'],
                        'email' => $payload['email'],
                        'company' => $payload['company'],
                        'role' => $payload['role'],
                        'service_key' => $payload['service'],
                        'service_label' => $this->rules->serviceLabel($payload['service']),
                        'challenge' => $payload['challenge'],
                        'timing' => $payload['timing'],
                        'locale' => current_locale(),
                        'public_reference' => $this->uniquePublicReference(),
                        'submission_hash' => $submissionHash,
                        'notification_status' => 'pending',
                        'received_at' => now(),
                    ]),
                    true,
                );
            });
        } catch (QueryException $exception) {
            $existing = ContactInquiry::query()
                ->where('submission_hash', $submissionHash)
                ->first();

            if ($existing !== null) {
                return new ConsultationSubmissionResult($existing, false);
            }

            throw $exception;
        }
    }

    private function uniquePublicReference(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $reference = 'IH-'.collect(range(1, 12))
                ->map(fn (): string => self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)])
                ->implode('');

            if (! ContactInquiry::query()->where('public_reference', $reference)->exists()) {
                return $reference;
            }
        }

        throw new \RuntimeException('Could not allocate a consultation reference.');
    }
}
