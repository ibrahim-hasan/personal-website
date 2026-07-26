<?php

namespace App\Models;

use App\Enums\ProjectEvidenceKind;
use App\Enums\ProjectEvidenceState;
use Database\Factories\ProjectEvidenceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ProjectEvidence extends Model
{
    /** @use HasFactory<ProjectEvidenceFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = [
        'project_id',
        'sort_order',
        'kind',
        'label',
        'result_text',
        'baseline_value',
        'result_value',
        'range_min',
        'range_max',
        'threshold_value',
        'unit',
        'direction',
        'baseline_period',
        'result_period',
        'method',
        'scope',
        'source_owner',
        'source_reference',
        'permission_reference',
        'state',
        'verified_by',
        'approved_by',
        'verified_at',
        'approved_at',
        'revoked_at',
        'is_public',
    ];

    protected $translatable = [
        'label',
        'result_text',
        'baseline_period',
        'result_period',
        'method',
        'scope',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'state' => ProjectEvidenceState::Draft->value,
        'is_public' => false,
    ];

    protected $hidden = [
        'source_owner',
        'source_reference',
        'permission_reference',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => ProjectEvidenceKind::class,
            'baseline_value' => 'decimal:6',
            'result_value' => 'decimal:6',
            'range_min' => 'decimal:6',
            'range_max' => 'decimal:6',
            'threshold_value' => 'decimal:6',
            'state' => ProjectEvidenceState::class,
            'verified_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'is_public' => 'boolean',
            'source_owner' => 'encrypted',
            'source_reference' => 'encrypted',
            'permission_reference' => 'encrypted',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @param Builder<ProjectEvidence> $query */
    public function scopePublicApproved(Builder $query): void
    {
        $query
            ->where('state', ProjectEvidenceState::Approved)
            ->where('is_public', true);
    }
}
