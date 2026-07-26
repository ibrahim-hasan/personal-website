<?php

namespace App\Actions\Athar;

use App\Enums\AtharContributionStatus;
use App\Models\AtharContribution;
use App\Support\AtharTextLimits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SealAtharContribution
{
    public function __construct(
        private readonly CreateContributorPublicNote $createPublication,
        private readonly SendAtharApproval $sendApproval,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(AtharContribution $contribution, array $payload, string $locale): AtharContribution
    {
        return DB::transaction(function () use ($contribution, $payload, $locale): AtharContribution {
            $record = AtharContribution::query()
                ->whereKey($contribution->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($record->sealed()) {
                return $record;
            }
            $payload = collect($payload)->map(fn ($value): string => trim((string) $value))->filter()->all();
            abort_if($payload === [], 422, __('athar.validation.required'));
            $source = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $record->forceFill(['sealed_payload' => $payload, 'draft_payload' => null, 'source_hash' => hash('sha256', $source), 'status' => AtharContributionStatus::Submitted, 'submitted_at' => now()])->save();
            $record->refresh();

            $privateText = (string) $payload['freeform'];
            $publicText = Str::length($privateText) <= AtharTextLimits::PUBLIC_MAX ? $privateText : '';
            $version = $this->createPublication->handle($record, [$locale => ['text' => $publicText, 'context' => '']]);
            $this->sendApproval->handle($version);

            return $record->fresh();
        });
    }
}
