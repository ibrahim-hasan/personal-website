<?php

namespace App\Actions\Athar;

use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Models\AtharInvitation;
use App\Models\User;
use App\Notifications\AtharInvitationNotification;
use App\Support\AtharAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateAtharInvitation
{
    /** @param array<string, mixed> $attributes */
    public function handle(User $creator, array $attributes, ?bool $sendEmail = null): array
    {
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

        $shareUrl = localized_route('athar.show', ['token' => $token], true, (string) ($attributes['preferred_locale'] ?? default_locale()));

        $invitation = DB::transaction(fn (): AtharInvitation => AtharInvitation::query()->create([
            'created_by' => $creator->getKey(), 'token_hash' => AtharAccess::tokenHash($token),
            'token_ciphertext' => $token, 'delivery_mode' => $sendEmail ? AtharInvitationDeliveryMode::Email : AtharInvitationDeliveryMode::Link,
            'email_hash' => $email === null ? null : hash_hmac('sha256', $email, (string) config('app.key')), 'email' => $email,
            'recipient_name' => $attributes['recipient_name'] ?? null, 'relationship' => $attributes['relationship'],
            'preferred_locale' => $attributes['preferred_locale'] ?? default_locale(),
            'personal_reason' => $attributes['personal_reason'] ?? null, 'prompt_snapshot' => $attributes['prompt_snapshot'] ?? null,
            'placement' => $attributes['placement'], 'placement_key' => $attributes['placement_key'] ?? null,
            'status' => AtharInvitationStatus::Sent, 'expires_at' => $attributes['expires_at'] ?? now()->addDays(14), 'sent_at' => $sendEmail ? now() : null,
        ]));

        if ($sendEmail) {
            Notification::route('mail', $invitation->email)->notify(new AtharInvitationNotification(
                $shareUrl,
                $invitation->preferred_locale,
            ));
        }

        return ['invitation' => $invitation, 'token' => $token, 'url' => $shareUrl, 'send_email' => $sendEmail];
    }
}
