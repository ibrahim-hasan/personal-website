<?php

namespace App\Actions\Athar;

use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Models\AtharInvitation;
use App\Models\User;
use App\Notifications\AtharInvitationNotification;
use App\Support\AtharAccess;
use App\Support\AtharPlacementDestination;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateAtharInvitation
{
    /**
     * Furthest an invitation may remain valid from creation.
     */
    private const int MAX_EXPIRY_DAYS = 90;

    private const int DEFAULT_EXPIRY_DAYS = 14;

    /** @param array<string, mixed> $attributes */
    public function handle(User $creator, array $attributes, ?bool $sendEmail = null): array
    {
        $placement = $this->placement($attributes['placement'] ?? null);
        $placementKey = AtharPlacementDestination::validatedKey($placement, $attributes['placement_key'] ?? null);
        $preferredLocale = $this->preferredLocale($attributes['preferred_locale'] ?? null);
        $token = AtharAccess::newToken();
        $sendEmail ??= array_key_exists('send_email', $attributes)
            ? (bool) $attributes['send_email']
            : filled($attributes['email'] ?? null);
        $email = $sendEmail && filled($attributes['email'] ?? null)
            ? Str::lower(trim((string) $attributes['email']))
            : null;

        if ($sendEmail && (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            throw ValidationException::withMessages([
                'email' => __('validation.email', ['attribute' => __('admin.fields.email_address')]),
            ]);
        }

        $shareUrl = localized_route('athar.show', ['token' => $token], true, $preferredLocale);

        $invitation = DB::transaction(fn (): AtharInvitation => AtharInvitation::query()->create([
            'created_by' => $creator->getKey(), 'token_hash' => AtharAccess::tokenHash($token),
            'token_ciphertext' => $token, 'delivery_mode' => $sendEmail ? AtharInvitationDeliveryMode::Email : AtharInvitationDeliveryMode::Link,
            'email_hash' => $email === null ? null : hash_hmac('sha256', $email, (string) config('app.key')), 'email' => $email,
            'recipient_name' => $attributes['recipient_name'] ?? null, 'relationship' => null,
            'preferred_locale' => $preferredLocale,
            'personal_reason' => null,
            'placement' => $placement, 'placement_key' => $placementKey, 'identity_display' => AtharIdentityDisplay::Anonymous,
            'status' => $sendEmail ? AtharInvitationStatus::Sent : AtharInvitationStatus::Ready,
            'expires_at' => $this->resolveExpiry($attributes['expires_at'] ?? null), 'sent_at' => $sendEmail ? now() : null,
        ]));

        if ($sendEmail) {
            Notification::route('mail', $invitation->email)->notify(new AtharInvitationNotification(
                $shareUrl,
                $invitation->preferred_locale,
            ));
        }

        return ['invitation' => $invitation, 'token' => $token, 'url' => $shareUrl, 'send_email' => $sendEmail];
    }

    /**
     * Resolve the invitation expiry, defaulting to a fortnight and clamping to
     * the configured maximum so a token can never outlive the hard ceiling.
     */
    private function resolveExpiry(mixed $expiresAt): CarbonInterface
    {
        $max = now()->addDays(self::MAX_EXPIRY_DAYS);
        $default = now()->addDays(self::DEFAULT_EXPIRY_DAYS);

        $resolved = match (true) {
            $expiresAt instanceof CarbonInterface => $expiresAt,
            is_string($expiresAt) && $expiresAt !== '' => now()->parse($expiresAt),
            default => $default,
        };

        return $resolved->isFuture() ? $resolved->min($max) : $default;
    }

    private function placement(mixed $value): AtharPlacement
    {
        $placement = $value instanceof AtharPlacement
            ? $value
            : AtharPlacement::tryFrom((string) $value);

        if ($placement === null) {
            throw ValidationException::withMessages([
                'placement' => __('athar.validation.placement_required'),
            ]);
        }

        return $placement;
    }

    private function preferredLocale(mixed $value): string
    {
        $locale = is_string($value) && $value !== '' ? $value : default_locale();

        if (! array_key_exists($locale, supported_locales())) {
            throw ValidationException::withMessages([
                'preferred_locale' => __('athar.validation.preferred_locale'),
            ]);
        }

        return $locale;
    }
}
