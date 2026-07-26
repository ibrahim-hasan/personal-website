<?php

declare(strict_types=1);

namespace App\Services\Projects;

use App\Enums\ProjectEvidenceKind;
use App\Models\ProjectEvidence;
use Illuminate\Validation\ValidationException;

final class ProjectEvidenceWorkflowValidator
{
    /** @var list<string> */
    private const LOCALES = ['ar', 'en'];

    /** @var list<string> */
    private const DIRECTIONS = ['increase', 'decrease', 'maintain'];

    public function assertReadyForVerification(ProjectEvidence $evidence): void
    {
        $this->throwIfInvalid($this->verificationPayloadErrors($evidence));
    }

    public function assertReadyForApproval(ProjectEvidence $evidence): void
    {
        $errors = $this->verificationPayloadErrors($evidence);

        if ($evidence->verified_by === null || $evidence->verified_at === null) {
            $errors['state'][] = __('project_evidence.errors.verification_required');
        }

        $this->throwIfInvalid($errors);
    }

    public function assertReadyForPublic(ProjectEvidence $evidence): void
    {
        $errors = $this->verificationPayloadErrors($evidence);

        if ($evidence->verified_by === null || $evidence->verified_at === null) {
            $errors['state'][] = __('project_evidence.errors.verification_required');
        }

        if ($evidence->approved_by === null || $evidence->approved_at === null) {
            $errors['state'][] = __('project_evidence.errors.approval_required');
        }

        $this->throwIfInvalid($errors);
    }

    /** @return array<string, list<string>> */
    private function verificationPayloadErrors(ProjectEvidence $evidence): array
    {
        $errors = [];

        $this->requireTranslations($evidence, 'label', $errors);
        $this->requireTranslations($evidence, 'result_text', $errors);

        if ($evidence->kind === ProjectEvidenceKind::Qualitative) {
            $this->validateQualitativeEvidence($evidence, $errors);

            return $errors;
        }

        $this->validateQuantitativeEvidence($evidence, $errors);

        return $errors;
    }

    /** @param array<string, list<string>> $errors */
    private function requireTranslations(ProjectEvidence $evidence, string $field, array &$errors): void
    {
        foreach (self::LOCALES as $locale) {
            if (! $this->hasText($evidence->getTranslationWithoutFallback($field, $locale))) {
                $errors["{$field}.{$locale}"][] = __('project_evidence.errors.translation_required');
            }
        }
    }

    /** @param array<string, list<string>> $errors */
    private function validateQualitativeEvidence(ProjectEvidence $evidence, array &$errors): void
    {
        if ($this->hasQuantitativeFields($evidence)) {
            $errors['kind'][] = __('project_evidence.errors.qualitative_measurement_not_allowed');
        }

        foreach (self::LOCALES as $locale) {
            foreach (['label', 'result_text'] as $field) {
                $value = $evidence->getTranslationWithoutFallback($field, $locale);

                if (is_string($value) && preg_match('/[0-9٠-٩]/u', $value) === 1) {
                    $errors["{$field}.{$locale}"][] = __('project_evidence.errors.qualitative_number_not_allowed');
                }
            }
        }
    }

    /** @param array<string, list<string>> $errors */
    private function validateQuantitativeEvidence(ProjectEvidence $evidence, array &$errors): void
    {
        if (! $this->hasText($evidence->unit)) {
            $errors['unit'][] = __('project_evidence.errors.unit_required');
        }

        if ($evidence->direction !== null && ! in_array($evidence->direction, self::DIRECTIONS, true)) {
            $errors['direction'][] = __('project_evidence.errors.direction_invalid');
        }

        foreach (['result_period', 'method', 'scope'] as $field) {
            $this->requireTranslations($evidence, $field, $errors);
        }

        if (! $this->hasText($evidence->source_owner)) {
            $errors['source_owner'][] = __('project_evidence.errors.private_source_owner_required');
        }

        if (! $this->hasText($evidence->source_reference)) {
            $errors['source_reference'][] = __('project_evidence.errors.private_source_reference_required');
        }

        if (! $this->hasText($evidence->permission_reference)) {
            $errors['permission_reference'][] = __('project_evidence.errors.private_permission_reference_required');
        }

        match ($evidence->kind) {
            ProjectEvidenceKind::Exact => $this->validateExactEvidence($evidence, $errors),
            ProjectEvidenceKind::Range => $this->validateRangeEvidence($evidence, $errors),
            ProjectEvidenceKind::Threshold => $this->validateThresholdEvidence($evidence, $errors),
            default => $errors['kind'][] = __('project_evidence.errors.kind_invalid'),
        };
    }

    /** @param array<string, list<string>> $errors */
    private function validateExactEvidence(ProjectEvidence $evidence, array &$errors): void
    {
        if ($evidence->baseline_value === null && $evidence->result_value === null) {
            $errors['result_value'][] = __('project_evidence.errors.exact_value_required');
        }

        if ($evidence->baseline_value !== null) {
            $this->requireTranslations($evidence, 'baseline_period', $errors);
        }
    }

    /** @param array<string, list<string>> $errors */
    private function validateRangeEvidence(ProjectEvidence $evidence, array &$errors): void
    {
        if ($evidence->range_min === null || $evidence->range_max === null) {
            $errors['range_min'][] = __('project_evidence.errors.range_required');

            return;
        }

        if ((float) $evidence->range_min > (float) $evidence->range_max) {
            $errors['range_max'][] = __('project_evidence.errors.range_invalid');
        }
    }

    /** @param array<string, list<string>> $errors */
    private function validateThresholdEvidence(ProjectEvidence $evidence, array &$errors): void
    {
        if ($evidence->threshold_value === null) {
            $errors['threshold_value'][] = __('project_evidence.errors.threshold_required');
        }
    }

    private function hasQuantitativeFields(ProjectEvidence $evidence): bool
    {
        return $evidence->baseline_value !== null
            || $evidence->result_value !== null
            || $evidence->range_min !== null
            || $evidence->range_max !== null
            || $evidence->threshold_value !== null
            || $this->hasText($evidence->unit)
            || $this->hasText($evidence->direction);
    }

    private function hasText(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    /** @param array<string, list<string>> $errors */
    private function throwIfInvalid(array $errors): void
    {
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
