<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectEvidence;
use App\Support\Media\PublicImage;

final class ProjectCaseStudyPresenter
{
    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     sector: string,
     *     summary: string,
     *     challenge: string,
     *     response: string,
     *     outcome: string,
     *     role: string,
     *     delivery_entity: ?string,
     *     delivery_period: ?string,
     *     confidentiality_note: string,
     *     sections: array<string, mixed>,
     *     image: string,
     *     image_media: array{src: string, srcset: string, width: int, height: int},
     *     image_alt: string,
     *     logo: string,
     *     logo_media: array{src: string, srcset: string, width: int, height: int},
     *     logo_alt: string,
     *     evidence: list<array<string, mixed>>,
     *     anonymized: bool
     * }
     */
    public function present(Project $project, ProjectCaseStudyPublicationEligibility $eligibility, string $locale): array
    {
        $anonymized = $project->isAnonymizedForPublic();
        $sections = $project->getTranslationWithoutFallback('case_study_sections', $locale);
        $mayRenderImage = $eligibility->mayRenderImage() && $project->mayRenderImage();
        $mayRenderLogo = $eligibility->mayRenderLogo() && $project->mayRenderLogo();

        return [
            'key' => $project->key,
            'title' => $this->translation($project, 'title', $locale),
            'sector' => $this->translation($project, 'sector', $locale),
            'summary' => $this->translation($project, 'summary', $locale),
            'challenge' => $this->translation($project, 'challenge', $locale),
            'response' => $this->translation($project, 'response', $locale),
            'outcome' => $this->translation($project, 'outcome', $locale),
            'role' => $this->translation($project, 'ibrahim_role', $locale),
            'delivery_entity' => $anonymized ? null : $project->delivery_entity?->value,
            'delivery_period' => $anonymized ? null : $this->translation($project, 'delivery_period', $locale),
            'confidentiality_note' => $this->translation($project, 'confidentiality_note', $locale),
            'sections' => is_array($sections) ? $sections : [],
            'image' => $mayRenderImage ? $project->imageUrl() : '',
            'image_media' => $mayRenderImage
                ? $project->responsiveImage()
                : PublicImage::hidden(Project::HERO_WIDTH, Project::HERO_HEIGHT),
            'image_alt' => $mayRenderImage ? $this->translation($project, 'image_alt', $locale) : '',
            'logo' => $mayRenderLogo ? $project->logoUrl() : '',
            'logo_media' => $mayRenderLogo
                ? $project->responsiveLogo()
                : PublicImage::hidden(Project::LOGO_WIDTH, Project::LOGO_HEIGHT),
            'logo_alt' => $mayRenderLogo ? $this->translation($project, 'logo_alt', $locale) : '',
            'evidence' => $eligibility->publicEvidence()
                ->map(fn (ProjectEvidence $evidence): array => $this->presentEvidence($evidence, $locale))
                ->all(),
            'anonymized' => $anonymized,
        ];
    }

    /** @return array<string, mixed> */
    private function presentEvidence(ProjectEvidence $evidence, string $locale): array
    {
        return [
            'kind' => $evidence->kind?->value,
            'label' => $this->translation($evidence, 'label', $locale),
            'result_text' => $this->translation($evidence, 'result_text', $locale),
            'baseline_value' => $evidence->baseline_value,
            'result_value' => $evidence->result_value,
            'range_min' => $evidence->range_min,
            'range_max' => $evidence->range_max,
            'threshold_value' => $evidence->threshold_value,
            'unit' => $evidence->unit,
            'direction' => $evidence->direction,
            'baseline_period' => $this->translation($evidence, 'baseline_period', $locale),
            'result_period' => $this->translation($evidence, 'result_period', $locale),
            'method' => $this->translation($evidence, 'method', $locale),
            'scope' => $this->translation($evidence, 'scope', $locale),
        ];
    }

    private function translation(Project|ProjectEvidence $model, string $field, string $locale): string
    {
        return (string) $model->getTranslationWithoutFallback($field, $locale);
    }
}
