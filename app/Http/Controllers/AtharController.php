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
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;
use App\Models\AtharInvitation;
use App\Models\AtharPublicationVersion;
use App\Support\AtharAccess;
use App\Support\AtharPublicationSnapshot;
use App\Support\AtharTextLimits;
use App\Support\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $pendingInput = $request->session()->pull($this->pendingInputKey($invitation), []);
        $contribution = AtharContribution::query()->firstOrCreate(['invitation_id' => $invitation->getKey()], ['status' => AtharContributionStatus::Draft]);
        $version = $contribution->publicationVersions()->latest('version')->first();
        if ($contribution->status === AtharContributionStatus::Draft) {
            return view('athar.reflection', ['invitation' => $invitation, 'contribution' => $contribution, 'pendingInput' => $pendingInput]);
        }
        if ($version === null && $contribution->sealed()) {
            // A sealed contribution should always have a version (SealAtharContribution
            // creates one in its transaction), but legacy data may not. Recover it here,
            // guarded by a lock so concurrent requests cannot stack duplicate versions.
            $version = DB::transaction(function () use ($contribution, $createPublication): ?AtharPublicationVersion {
                $fresh = AtharContribution::query()->whereKey($contribution->getKey())->lockForUpdate()->firstOrFail();

                return $fresh->publicationVersions()->latest('version')->first()
                    ?? $createPublication->handle($fresh, [
                        app()->getLocale() => [
                            'text' => Str::length((string) data_get($fresh->sealed_payload, 'freeform')) <= AtharTextLimits::PUBLIC_MAX
                                ? (string) data_get($fresh->sealed_payload, 'freeform')
                                : '',
                            'context' => '',
                        ],
                    ]);
            });
            if ($version !== null && $version->status === AtharPublicationStatus::Draft) {
                $sendApproval->handle($version);
            }
        }
        if ($version !== null && in_array($version->status->value, ['draft', 'awaiting_approval'], true)) {
            return view('athar.receipt', [
                'invitation' => $invitation,
                'contribution' => $contribution,
                'version' => $version,
                'pendingInput' => $pendingInput,
                'destination' => AtharPublicationSnapshot::destinationLabel($version, app()->getLocale()),
            ]);
        }

        if ($version === null) {
            return view('athar.unavailable');
        }

        return view('athar.published', [
            'invitation' => $invitation,
            'contribution' => $contribution,
            'version' => $version,
            'destination' => AtharPublicationSnapshot::destinationLabel($version, app()->getLocale()),
        ]);
    }

    public function requestCode(Request $request, string $token, IssueAtharAccessChallenge $issue): RedirectResponse
    {
        $invitation = AtharAccess::invitation($token);
        if ($invitation === null || ! $invitation->isAccessible()) {
            return $this->atharRedirect($token);
        }
        if ($invitation->delivery_mode === AtharInvitationDeliveryMode::Link) {
            return $this->atharRedirect($token);
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

        return $this->atharRedirect($token);
    }

    public function verifyCode(Request $request, string $token, VerifyAtharAccessChallenge $verify): RedirectResponse
    {
        $invitation = AtharAccess::invitation($token);
        if ($invitation === null || ! $invitation->isAccessible()) {
            return $this->atharRedirect($token);
        }
        $data = Validator::make([
            'code' => AtharAccess::normalizeCode((string) $request->input('code')),
        ], ['code' => ['required', 'digits:6']])->validate();
        if (! $verify->handle($invitation, (string) $data['code'], $request)) {
            return back()->withErrors(['code' => __('athar.access.invalid_code')]);
        }

        return $this->atharRedirect($token);
    }

    public function saveDraft(Request $request, string $token, SaveAtharContributionDraft $save): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        if ($invitation instanceof RedirectResponse) {
            return $invitation;
        }
        $contribution = $this->contribution($invitation);
        $data = Validator::make($request->all(), ['freeform' => ['nullable', 'string', 'max:'.AtharTextLimits::REFLECTION_MAX]])->validate();
        $save->handle($contribution, ['freeform' => $data['freeform'] ?? '']);

        return $this->atharRedirect($token)->with('status', __('athar.reflection.draft_saved'));
    }

    public function seal(Request $request, string $token, SealAtharContribution $seal): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        if ($invitation instanceof RedirectResponse) {
            return $invitation;
        }
        $data = Validator::make($request->all(), ['freeform' => ['required', 'string', 'min:3', 'max:'.AtharTextLimits::REFLECTION_MAX]])->validate();
        $seal->handle($this->contribution($invitation), ['freeform' => $data['freeform']], app()->getLocale());

        return $this->atharRedirect($token);
    }

    public function approve(Request $request, string $token, ApproveAndPublishAtharVersion $approve): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        if ($invitation instanceof RedirectResponse) {
            return $invitation;
        }
        $version = $this->contribution($invitation)->publicationVersions()->latest('version')->firstOrFail();
        $data = $this->publicationData($request, true, true);
        $approve->handle($version, $request, $data['text'], $data['identity_display'], $data['display_name']);

        return $this->atharRedirect($token);
    }

    public function saveApprovalDraft(Request $request, string $token, SaveAtharPublicationDraft $save): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        if ($invitation instanceof RedirectResponse) {
            return $invitation;
        }
        $version = $this->contribution($invitation)->publicationVersions()->latest('version')->firstOrFail();
        $data = $this->publicationData($request, false, false);
        $save->handle($version, $data['text'], $data['identity_display'], $data['display_name']);

        return $this->atharRedirect($token)->with('status', __('athar.approval.draft_saved'));
    }

    public function withdraw(Request $request, string $token, WithdrawAtharPublication $withdraw): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        if ($invitation instanceof RedirectResponse) {
            return $invitation;
        }
        Validator::make($request->all(), ['confirm' => ['accepted']])->validate();
        $version = $this->contribution($invitation)->publicationVersions()->latest('version')->firstOrFail();
        $withdraw->handle($version, $request);

        return $this->atharRedirect($token);
    }

    public function deletion(Request $request, string $token, RequestAtharPrivateDataDeletion $deletion): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        if ($invitation instanceof RedirectResponse) {
            return $invitation;
        }
        Validator::make($request->all(), ['confirm' => ['accepted']])->validate();
        $deletion->handle($this->contribution($invitation), $request);

        return back()->with('status', __('athar.published.deletion_requested'));
    }

    public function cancelDeletion(Request $request, string $token, CancelAtharPrivateDataDeletion $cancel): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        if ($invitation instanceof RedirectResponse) {
            return $invitation;
        }
        $cancel->handle($this->contribution($invitation));

        return $this->atharRedirect($token);
    }

    public function restore(Request $request, string $token, RestoreAtharPublication $restore): RedirectResponse
    {
        $invitation = $this->verifiedInvitation($request, $token);
        if ($invitation instanceof RedirectResponse) {
            return $invitation;
        }
        Validator::make($request->all(), ['confirm' => ['accepted']])->validate();
        $version = $this->contribution($invitation)->publicationVersions()->latest('version')->firstOrFail();
        $restore->handle($version, $request);

        return $this->atharRedirect($token);
    }

    private function verifiedInvitation(Request $request, string $token): AtharInvitation|RedirectResponse
    {
        $invitation = AtharAccess::invitation($token);
        if ($invitation === null || ! $invitation->isAccessible()) {
            return $this->atharRedirect($token);
        }
        if (! AtharAccess::verified($request, $invitation)) {
            $request->session()->put($this->pendingInputKey($invitation), [
                'freeform' => $request->input('freeform'),
                'text' => $request->input('text'),
                'identity_display' => $request->input('identity_display'),
                'display_name' => $request->input('display_name'),
            ]);

            return $this->atharRedirect($token)
                ->with('status', __('athar.access.session_expired'));
        }

        return $invitation;
    }

    /**
     * @return array{text: string, identity_display: AtharIdentityDisplay, display_name: string}
     */
    private function publicationData(Request $request, bool $requiresText, bool $requiresConsent): array
    {
        $rules = [
            'text' => $requiresText
                ? ['required', 'string', 'min:3', 'max:'.AtharTextLimits::PUBLIC_MAX]
                : ['nullable', 'string', 'max:'.AtharTextLimits::PUBLIC_MAX],
            'identity_display' => ['required', Rule::enum(AtharIdentityDisplay::class)],
            'display_name' => ['nullable', 'string', 'max:255'],
        ];
        if ($requiresConsent) {
            $rules['consent'] = ['accepted'];
        }

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request): void {
            $identityDisplay = AtharIdentityDisplay::tryFrom((string) $request->input('identity_display'));
            if ($identityDisplay !== null && $identityDisplay !== AtharIdentityDisplay::Anonymous && blank($request->input('display_name'))) {
                $validator->errors()->add('display_name', __('athar.validation.display_name_required'));
            }
        });
        $data = $validator->validate();
        $identityDisplay = AtharIdentityDisplay::from((string) $data['identity_display']);

        return [
            'text' => (string) ($data['text'] ?? ''),
            'identity_display' => $identityDisplay,
            'display_name' => $this->displayName($identityDisplay, (string) ($data['display_name'] ?? '')),
        ];
    }

    private function displayName(AtharIdentityDisplay $identityDisplay, string $displayName): string
    {
        $displayName = trim($displayName);
        if ($identityDisplay === AtharIdentityDisplay::Anonymous) {
            return '';
        }
        if ($identityDisplay === AtharIdentityDisplay::FirstName) {
            $names = preg_split('/\s+/u', $displayName, 2);

            return is_array($names) ? (string) ($names[0] ?? '') : $displayName;
        }

        return $displayName;
    }

    private function pendingInputKey(AtharInvitation $invitation): string
    {
        return 'athar.pending_input.'.$invitation->getKey();
    }

    private function atharRedirect(string $token): RedirectResponse
    {
        return redirect()->to(localized_route('athar.show', ['token' => $token]));
    }

    private function contribution(AtharInvitation $invitation): AtharContribution
    {
        return $invitation->contribution()->firstOrCreate([], ['status' => AtharContributionStatus::Draft]);
    }
}
