<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class DeploymentWorkflowTest extends TestCase
{
    public function test_ci_runs_only_for_production_pushes_without_manual_release_controls(): void
    {
        $workflow = $this->workflow();

        $this->assertStringContainsString('name: CI and deploy production', $workflow);
        $this->assertStringContainsString("push:\n    branches:\n      - production", $workflow);
        $this->assertStringContainsString('name: CI checks', $workflow);
        $this->assertStringContainsString('php artisan migrate:fresh --force --no-interaction', $workflow);
        $this->assertStringContainsString('php artisan content:lint', $workflow);
        $this->assertStringContainsString('php artisan test --compact', $workflow);
        $this->assertStringContainsString('composer lint:check', $workflow);

        $validationJob = explode('  deploy:', $workflow, 2)[0];

        $this->assertStringNotContainsString('${{ secrets.', $validationJob);
        $this->assertStringNotContainsString('workflow_dispatch:', $workflow);
        $this->assertStringNotContainsString('dispatch_guard:', $workflow);
        $this->assertStringNotContainsString('rollback:', $workflow);
        $this->assertStringNotContainsString('inputs.', $workflow);
        $this->assertStringNotContainsString('actions/upload-artifact', $workflow);
        $this->assertStringNotContainsString('actions/download-artifact', $workflow);
        $this->assertStringNotContainsString('release-artifact', $workflow);
        $this->assertStringNotContainsString('staging', $workflow);
    }

    public function test_a_successful_production_push_deploys_the_pushed_commit(): void
    {
        $workflow = $this->workflow();

        $this->assertStringContainsString('  deploy:', $workflow);
        $this->assertStringContainsString('needs: validate', $workflow);
        $this->assertStringContainsString("if: github.event_name == 'push' && github.ref == 'refs/heads/production'", $workflow);
        $this->assertStringContainsString('name: production', $workflow);
        $this->assertStringContainsString('ref: ${{ github.sha }}', $workflow);
        $this->assertStringContainsString('RELEASE_REVISION: ${{ github.sha }}', $workflow);
        $this->assertStringContainsString('npm run build', $workflow);
        $this->assertStringContainsString('vendor/bin/dep deploy production --revision "$RELEASE_REVISION" --no-interaction', $workflow);
    }

    public function test_deployer_requires_shared_passport_keys_and_preserves_safe_release_checks(): void
    {
        $recipe = file_get_contents(base_path('deploy.php'));

        $this->assertNotFalse($recipe);
        $this->assertStringContainsString("set('keep_releases', 5);", $recipe);
        $this->assertStringContainsString("host('production')", $recipe);
        $this->assertStringNotContainsString("host('staging')", $recipe);
        $this->assertStringContainsString("task('deploy:assert-explicit-revision'", $recipe);
        $this->assertStringContainsString("task('artisan:assert-passport-keys'", $recipe);
        $this->assertStringNotContainsString('passport:keys', $recipe);
        $this->assertStringContainsString("before('artisan:migrate', 'artisan:assert-passport-keys');", $recipe);
        $this->assertStringContainsString("task('artisan:record-scheduler-heartbeat'", $recipe);
        $this->assertStringContainsString("task('artisan:release-check'", $recipe);
        $this->assertStringContainsString("task('deploy:readiness-check'", $recipe);
        $this->assertStringContainsString('operations:check-readiness --url=', $recipe);
        $this->assertStringContainsString('DEPLOY_READINESS_URL', $recipe);
        $this->assertStringNotContainsString('DEPLOY_READINESS_SECRET', $recipe);
        $this->assertStringContainsString("after('deploy:symlink', 'artisan:record-scheduler-heartbeat');", $recipe);
        $this->assertStringContainsString("after('deploy:symlink', 'artisan:release-check');", $recipe);
        $this->assertStringContainsString("after('deploy:symlink', 'deploy:readiness-check');", $recipe);
    }

    private function workflow(): string
    {
        $workflow = file_get_contents(base_path('.github/workflows/deploy.yml'));

        $this->assertNotFalse($workflow);

        return $workflow;
    }
}
