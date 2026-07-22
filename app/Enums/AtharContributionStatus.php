<?php

namespace App\Enums;

enum AtharContributionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Private = 'private';
    case PublicChoice = 'public_choice';
    case AwaitingSuggestion = 'awaiting_suggestion';
    case AwaitingApproval = 'awaiting_approval';
    case Published = 'published';
    case Withdrawn = 'withdrawn';
    case DeletionRequested = 'deletion_requested';

    public function label(): string
    {
        return __("admin.athar.contribution_statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted, self::Private => 'info',
            self::PublicChoice => 'primary',
            self::AwaitingSuggestion, self::AwaitingApproval, self::DeletionRequested => 'warning',
            self::Published => 'success',
            self::Withdrawn => 'danger',
        };
    }
}
