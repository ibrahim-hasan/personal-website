<?php

namespace App\Services\Projects;

use App\Models\ProjectEvidence;
use Illuminate\Support\Collection;

/**
 * @phpstan-type PublicationViolation string
 * @phpstan-type PublicEvidence Collection<int, ProjectEvidence>
 */
final class ProjectCaseStudyPublicationEligibility
{
    /**
     * @param  list<PublicationViolation>  $violations
     * @param  PublicEvidence  $publicEvidence
     */
    public function __construct(
        private readonly array $violations,
        private readonly Collection $publicEvidence,
        private readonly bool $mayRenderImage,
        private readonly bool $mayRenderLogo,
    ) {}

    public function isEligible(): bool
    {
        return $this->violations === [];
    }

    /** @return list<PublicationViolation> */
    public function violations(): array
    {
        return $this->violations;
    }

    public function hasViolation(string $violation): bool
    {
        return in_array($violation, $this->violations, true);
    }

    /** @return PublicEvidence */
    public function publicEvidence(): Collection
    {
        return $this->publicEvidence;
    }

    public function mayRenderImage(): bool
    {
        return $this->mayRenderImage;
    }

    public function mayRenderLogo(): bool
    {
        return $this->mayRenderLogo;
    }
}
