<?php

namespace App\Http\Controllers;

use App\Actions\Athar\ApproveAndPublishAtharVersion;
use App\Actions\Athar\CancelAtharPrivateDataDeletion;
use App\Actions\Athar\CreateContributorPublicNote;
use App\Actions\Athar\IssueAtharAccessChallenge;
use App\Actions\Athar\RequestAtharPrivateDataDeletion;
use App\Actions\Athar\RestoreAtharPublication;
use App\Actions\Athar\SaveAtharContributionDraft;
use App\Actions\Athar\SaveAtharPublicationDraft;
use App\Actions\Athar\SealAtharContribution;
use App\Actions\Athar\SendAtharApproval;
use App\Actions\Athar\VerifyAtharAccessChallenge;
use App\Actions\Athar\WithdrawAtharPublication;
use App\Enums\AtharContributionStatus;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;
use App\Models\AtharInvitation;
use App\Models\AtharPublicationVersion;
use App\Support\AtharAccess;
use App\Support\AtharTextLimits;
use App\Support\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AtharController extends Controller
{
    public function __construct(private readonly Turnstile $turnstile) {}

    public function show(Request $request, string $token, CreateContributorPublicNote $createPublication, SendAtharApproval $sendApproval): View
    {
        $invitation = AtharAccess::invitation($token);
        if ($invitation === null || ! $invitation->isAccessible()) {
            return view('athar.unavailable');
        }
        if (! AtharAccess::verified($request, $invitation)) {
            return view('athar.access', [
                'invitation' => $invitation,
                'codeSent' => $request->session()->get('athar.code_sent') === $invitation->getKey(),
            ]);
        }
        $contribution = AtharContribution::query()->firstOrCreate(['invitation_id' => $invitation->getKey()], ['status' => AtharContributionStatus::Draft]);
        $version = $contribution->publicationVersions()->latest('version')->first();
        if ($contribution->status === AtharContributionStatus::Draft) {
            return view('athar.reflection', ['invitation' => $invitation, 'contribution' => $contribution]);
        }
        if ($version === null && $contribution->sealed()) {
            // A sealed contribution should always have a version (SealAtharContribution
            // creates one in its transaction), but legacy data may not. Recover it here,
            // guarded by a lock so concurrent requests cannot stack duplicate versions.
            $version = DB::transaction(function () use ($contribution, $invitation, $createPublication): ?AtharPublicationVersion {
                $fresh = AtharContribution::query()->whereKey($contribution->getKey())->lockForUpdate()->firstOrFail();

                return $fresh->publicationVersions()->latest('version')->first()
                    ?? $createPublication->handle($fresh, [
                        $invitation->preferred_locale => [
                            'text' => (string) data_get($fresh->sealed_payload, 'freeform'),
                            'context' => '',
                        ],
                    ]);
            });
            if ($version !== null && $version->status === AtharPublicationStatus::Draft) {
                $sendApproval->handle($version);
            }
        }
        if ($version !== null && in_array($version->status->value, ['draft', 'awaiting_approval'], true)) {
            return view('athar.receipt', ['invitation' => $invitation, 'contribution' => $contribution, 'version' => $version]);
        }

        if ($version === null) {
            return view('athar.unavailable');
        }

        return view('athar.published', ['invitation' => $invitation, 'contribution' => $contribution, 'version' => $version]);
    }

    public function requestCode(Request $request, string $token, IssueAtharAccessChallenge $issue): RedirectResponse
    {
        $invitation = AtharAccess::invitation($token);
        if ($invitation === null || ! $invitation->isAccessible()) {
            return redirect()->route('athar.show', ['token' => $token]);
        }
        if ($invitation->delivery_mode === AtharInvitationDeliveryMode::Link) {
            return redirect()->route('athar.show', ['token' => $token]);
        }

        if ($this->turnstile->enabled()
            && ! $this->turnstile->verify((string) $request->input('cf-turnstile-response'), $this->turnstile->clientIp($request))) {
            return back()->withErrors(['turnstile' => __('validation.turnstile')]);
        }

        $isResend = $request->session()->get('athar.code_sent') === $invitation->getKey()
            && blank($request->input('email'));
        $emailHash = $isResend
            ? $invitation->email_hash
            : hash_hmac('sha256', strtolower(trim((string) Validator::make($request->all(), ['email' => ['required', 'email', 'max:255']])->validate()['email'])), (string) config('app.key'));
        if (! is_string($invitation->email_hash) || ! is_string($emailHash) || ! hash_equals($invitation->email_hash, $emailHash)) {
            return back()->withErrors(['email' => __('athar.validation.email')]);
        }
        $issue->handle($invitation, $request);
        $request->session()->put('athar.code_sent', $invitation->getKey());

        return redirect()->route('athar.show', ['token' => $token]);
    }

    public function verifyCode(Request $request, string $token, VerifyAtharAccessChallenge $verify): RedirectResponse
    {
        $invitation = AtharAccess::invitation($token);
        if ($invitation === null || ! $invitation->isAccessible()) {
            return redirect()->route('athar.show', ['token' => $token]);
        }
        $data = Validator::make($request->all(), ['code' => ['required', 'digits:6']])->validate();
        if (! $verify->handle($invitation, (string) $data['code'], $request)) {
            return back()->withErrors(['code' => __('athar.access.invalid_code')]);
        }

        return redirect()->route('athar.show', ['token' => $token]);
    }

    public function saveDraft(Request $request, string $token, SaveAtharContributionDraft $save): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        $contribution = $this->contribution($invitation);
        $data = Validator::make($request->all(), ['freeform' => ['nullable', 'string', 'max:'.AtharTextLimits::REFLECTION_MAX]])->validate();
        $save->handle($contribution, ['freeform' => $data['freeform'] ?? '']);

        return back()->with('status', __('athar.reflection.draft_saved'));
    }

    public function seal(Request $request, string $token, SealAtharContribution $seal): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        $data = Validator::make($request->all(), ['freeform' => ['required', 'string', 'min:3', 'max:'.AtharTextLimits::REFLECTION_MAX]])->validate();
        $seal->handle($this->contribution($invitation), ['freeform' => $data['freeform']]);

        return redirect()->route('athar.show', ['token' => $token]);
    }

    public function approve(Request $request, string $token, ApproveAndPublishAtharVersion $approve): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        $version = $this->contribution($invitation)->publicationVersions()->latest('version')->firstOrFail();
        $data = Validator::make($request->all(), ['consent' => ['accepted'], 'text' => ['required', 'string', 'min:3', 'max:'.AtharTextLimits::PUBLIC_MAX]])->validate();
        $payload = $version->public_payload;
        $locale = array_key_first($payload);
        abort_unless(is_string($locale) && isset($payload[$locale]), 422);
        $payload[$locale]['text'] = (string) $data['text'];
        $version->forceFill([
            'public_payload' => $payload,
            'snapshot_hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        ])->save();
        $approve->handle($version, $request);

        return redirect()->route('athar.show', ['token' => $token]);
    }

    public function saveApprovalDraft(Request $request, string $token, SaveAtharPublicationDraft $save): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        $version = $this->contribution($invitation)->publicationVersions()->latest('version')->firstOrFail();
        $data = Validator::make($request->all(), ['text' => ['nullable', 'string', 'max:'.AtharTextLimits::PUBLIC_MAX]])->validate();
        $save->handle($version, (string) ($data['text'] ?? ''));

        return back()->with('status', __('athar.approval.draft_saved'));
    }

    public function withdraw(Request $request, string $token, WithdrawAtharPublication $withdraw): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        Validator::make($request->all(), ['confirm' => ['accepted']])->validate();
        $version = $this->contribution($invitation)->publicationVersions()->latest('version')->firstOrFail();
        $withdraw->handle($version, $request);

        return redirect()->route('athar.show', ['token' => $token]);
    }

    public function deletion(Request $request, string $token, RequestAtharPrivateDataDeletion $deletion): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        Validator::make($request->all(), ['confirm' => ['accepted']])->validate();
        $deletion->handle($this->contribution($invitation), $request);

        return back()->with('status', __('athar.published.deletion_requested'));
    }

    public function cancelDeletion(Request $request, string $token, CancelAtharPrivateDataDeletion $cancel): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        $cancel->handle($this->contribution($invitation));

        return redirect()->route('athar.show', ['token' => $token]);
    }

    public function restore(Request $request, string $token, RestoreAtharPublication $restore): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        Validator::make($request->all(), ['confirm' => ['accepted']])->validate();
        $version = $this->contribution($invitation)->publicationVersions()->latest('version')->firstOrFail();
        $restore->handle($version, $request);

        return redirect()->route('athar.show', ['token' => $token]);
    }

    private function verifiedInvitation(Request $request, string $token): AtharInvitation
    {
        $invitation = AtharAccess::invitation($token);
        abort_unless($invitation !== null && AtharAccess::verified($request, $invitation), 404);

        return $invitation;
    }

    private function contribution(AtharInvitation $invitation): AtharContribution
    {
        return $invitation->contribution()->firstOrCreate([], ['status' => AtharContributionStatus::Draft]);
    }
}
