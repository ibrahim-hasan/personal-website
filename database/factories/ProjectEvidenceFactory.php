<?php

namespace Database\Factories;

use App\Enums\ProjectEvidenceKind;
use App\Enums\ProjectEvidenceState;
use App\Models\Project;
use App\Models\ProjectEvidence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectEvidence>
 */
class ProjectEvidenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'sort_order' => 0,
            'kind' => ProjectEvidenceKind::Qualitative,
            'label' => [
                'ar' => 'ملاحظة موثقة',
                'en' => 'Documented observation',
            ],
            'result_text' => [
                'ar' => 'تحسّن وضوح سير العمل لدى الفريق.',
                'en' => 'The team gained a clearer workflow.',
            ],
            'state' => ProjectEvidenceState::Draft,
            'is_public' => false,
        ];
    }
}
