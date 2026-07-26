<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class DeploymentWorkflowTest extends TestCase
{
    public function test_the_release_workflow_only_deploys_after_a_manual_exact_sha_validation(): void
    {
        $workflow = $this->workflow();

        $this->assertStringContainsString('pull_request:', $workflow);
        $this->assertStringContainsString("push:\n    branches:\n      - '**'", $workflow);
        $this->assertStringContainsString('workflow_dispatch:', $workflow);
        $this->assertStringContainsString("inputs.operation == 'release'", $workflow);
        $this->assertStringContainsString('Full 40-character commit SHA to release', $workflow);
        $this->assertStringContainsString('git merge-base --is-ancestor "$revision" origin/production', $workflow);
        $this->assertStringContainsString('vendor/bin/dep deploy "$DEPLOY_TARGET" --revision "$RELEASE_REVISION" --no-interaction', $workflow);
        $this->assertStringContainsString('php artisan content:lint', $workflow);

        $validationJob = explode('  release:', $workflow, 2)[0];

        $this->assertStringNotContainsString('${{ secrets.', $validationJob);
    }

    public function test_the_workflow_reuses_the_staging_artifact_for_production_and_retains_release_evidence(): void
    {
        $workflow = $this->workflow();

        $this->assertStringContainsString('source_artifact_run_id:', $workflow);
        $this->assertStringContainsString('Production releases require the successful staging artifact workflow run ID.', $workflow);
        $this->assertStringContainsString('run-id: ${{ inputs.source_artifact_run_id }}', $workflow);
        $this->assertStringContainsString('The artifact revision does not match the validated release revision.', $workflow);
        $this->assertStringContainsString('retention-days: 90', $workflow);
    }

    public function test_deployer_requires_shared_passport_keys_and_preserves_safe_rollback_behavior(): void
    {
        $recipe = file_get_contents(base_path('deploy.php'));

        $this->assertNotFalse($recipe);
        $this->assertStringContainsString("set('keep_releases', 5);", $recipe);
        $this->assertStringContainsString("host('staging')", $recipe);
        $this->assertStringContainsString("host('production')", $recipe);
        $this->assertStringContainsString("task('deploy:assert-explicit-revision'", $recipe);
        $this->assertStringContainsString("task('artisan:assert-passport-keys'", $recipe);
        $this->assertStringNotContainsString('passport:keys', $recipe);
        $this->assertStringContainsString("before('artisan:migrate', 'artisan:assert-passport-keys');", $recipe);
        $this->assertStringContainsString("before('rollback', 'artisan:assert-passport-keys');", $recipe);
        $this->assertStringContainsString("after('rollback', 'artisan:horizon-terminate');", $recipe);
        $this->assertStringContainsString("after('rollback', 'artisan:schedule-interrupt');", $recipe);
        $this->assertStringContainsString("task('artisan:record-scheduler-heartbeat'", $recipe);
        $this->assertStringContainsString("task('artisan:release-check'", $recipe);
        $this->assertStringContainsString("task('deploy:readiness-check'", $recipe);
        $this->assertStringContainsString('operations:check-readiness --url=', $recipe);
        $this->assertStringContainsString('DEPLOY_READINESS_URL', $recipe);
        $this->assertStringNotContainsString('DEPLOY_READINESS_SECRET', $recipe);
        $this->assertStringContainsString("after('deploy:symlink', 'artisan:record-scheduler-heartbeat');", $recipe);
        $this->assertStringContainsString("after('deploy:symlink', 'artisan:release-check');", $recipe);
        $this->assertStringContainsString("after('deploy:symlink', 'deploy:readiness-check');", $recipe);
        $this->assertStringContainsString("after('rollback', 'artisan:record-scheduler-heartbeat');", $recipe);
        $this->assertStringContainsString("after('rollback', 'artisan:release-check');", $recipe);
        $this->assertStringContainsString("after('rollback', 'deploy:health-check');", $recipe);
        $this->assertStringContainsString("after('rollback', 'deploy:readiness-check');", $recipe);
    }

    private function workflow(): string
    {
        $workflow = file_get_contents(base_path('.github/workflows/deploy.yml'));

        $this->assertNotFalse($workflow);

        return $workflow;
    }
}
