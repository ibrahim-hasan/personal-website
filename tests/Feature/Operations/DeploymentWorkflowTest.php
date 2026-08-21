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
        $this->assertStringContainsString('GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON: ${{ secrets.GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON }}', $workflow);
        $this->assertStringContainsString('WEBSITE_METRICS_CLIENT_SECRET: ${{ secrets.WEBSITE_METRICS_CLIENT_SECRET }}', $workflow);
        $this->assertStringContainsString('WEBSITE_PERFORMANCE_WEBSITE_URL: ${{ vars.WEBSITE_PERFORMANCE_WEBSITE_URL }}', $workflow);
        $this->assertStringContainsString('npm run build', $workflow);
        $this->assertStringContainsString('vendor/bin/dep deploy production --revision "$RELEASE_REVISION" --no-interaction', $workflow);
        $this->assertStringNotContainsString('DEPLOY_READINESS_URL', $workflow);
    }

    public function test_deployment_credentials_are_scoped_to_the_deployer_step(): void
    {
        $workflow = $this->workflow();
        $deployJob = explode('  deploy:', $workflow, 2);

        $this->assertCount(2, $deployJob);

        $jobConfiguration = explode('    steps:', $deployJob[1], 2);

        $this->assertCount(2, $jobConfiguration);
        $this->assertStringNotContainsString('${{ secrets.', $jobConfiguration[0]);
        $this->assertStringNotContainsString('GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON', $jobConfiguration[0]);
        $this->assertStringNotContainsString('WEBSITE_METRICS_CLIENT_SECRET', $jobConfiguration[0]);
        $this->assertStringNotContainsString('DEPLOY_SSH_PRIVATE_KEY', $jobConfiguration[0]);

        $deploymentStep = explode('      - name: Configure deployment SSH', $workflow, 2);

        $this->assertCount(2, $deploymentStep);
        $this->assertStringNotContainsString('GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON', $deploymentStep[0]);
        $this->assertStringNotContainsString('WEBSITE_METRICS_CLIENT_SECRET', $deploymentStep[0]);
        $this->assertStringNotContainsString('DEPLOY_SSH_PRIVATE_KEY', $deploymentStep[0]);

        $stepConfiguration = explode('        run:', $deploymentStep[1], 2);

        $this->assertCount(2, $stepConfiguration);
        $this->assertStringContainsString('GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON: ${{ secrets.GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON }}', $stepConfiguration[0]);
        $this->assertStringContainsString('WEBSITE_METRICS_CLIENT_SECRET: ${{ secrets.WEBSITE_METRICS_CLIENT_SECRET }}', $stepConfiguration[0]);
        $this->assertStringContainsString('DEPLOY_SSH_PRIVATE_KEY: ${{ secrets.DEPLOY_SSH_PRIVATE_KEY }}', $stepConfiguration[0]);
        $this->assertStringContainsString('WEBSITE_PERFORMANCE_WEBSITE_URL: ${{ vars.WEBSITE_PERFORMANCE_WEBSITE_URL }}', $stepConfiguration[0]);
        $this->assertStringContainsString('RELEASE_REVISION: ${{ github.sha }}', $stepConfiguration[0]);
    }

    public function test_deployer_requires_shared_passport_and_reporting_credentials_and_uses_the_public_health_check_as_its_completion_gate(): void
    {
        $recipe = file_get_contents(base_path('deploy.php'));

        $this->assertNotFalse($recipe);
        $this->assertStringContainsString("set('keep_releases', 5);", $recipe);
        $this->assertStringContainsString("host('production')", $recipe);
        $this->assertStringNotContainsString("host('staging')", $recipe);
        $this->assertStringContainsString("task('deploy:assert-explicit-revision'", $recipe);
        $this->assertStringContainsString("task('artisan:assert-passport-keys'", $recipe);
        $this->assertStringContainsString("task('deploy:provision-website-performance-reporting'", $recipe);
        $this->assertStringNotContainsString('passport:keys', $recipe);
        $this->assertStringContainsString("before('artisan:migrate', 'artisan:assert-passport-keys');", $recipe);
        $this->assertStringContainsString("after('deploy:writable', 'deploy:provision-website-performance-reporting');", $recipe);
        $this->assertStringContainsString("task('artisan:sanitize-website-performance-snapshots', artisan('website:performance-sanitize-snapshots --no-interaction'));", $recipe);
        $this->assertSame(1, substr_count($recipe, 'website:performance-sanitize-snapshots --no-interaction'));
        $this->assertStringContainsString("before('deploy:symlink', 'artisan:sanitize-website-performance-snapshots');", $recipe);
        $this->assertStringContainsString('run("chmod 600 $keyPath");', $recipe);
        $this->assertStringContainsString('Shared Passport key material could not be secured with mode 0600.', $recipe);
        $this->assertStringContainsString('GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON', $recipe);
        $this->assertStringContainsString('WEBSITE_METRICS_CLIENT_SECRET', $recipe);
        $this->assertStringContainsString('GOOGLE_REPORTING_CREDENTIALS_PATH', $recipe);
        $this->assertStringContainsString('WEBSITE_PERFORMANCE_WEBSITE_URL', $recipe);
        $this->assertStringContainsString('/^APP_URL=/d; /^GOOGLE_REPORTING_CREDENTIALS_PATH=/d', $recipe);
        $this->assertStringContainsString('printf \'\\nAPP_URL=%s\\n\' __WEBSITE_ORIGIN__ >> "$temporary_file"', $recipe);
        $this->assertStringContainsString('WEBSITE_METRICS_API_URL', $recipe);
        $this->assertStringContainsString('WEBSITE_METRICS_TOKEN_URL', $recipe);
        $this->assertStringContainsString('/api/v1/metrics/website', $recipe);
        $this->assertStringContainsString('/oauth/token', $recipe);
        $this->assertStringContainsString("getenv('WEBSITE_PERFORMANCE_WEBSITE_URL')", $recipe);
        $this->assertSame(1, substr_count($recipe, "getenv('DEPLOY_HEALTH_URL')"));
        $this->assertStringContainsString('%website_metrics_client_secret%', $recipe);
        $this->assertStringContainsString("secrets: ['website_metrics_client_secret' => escapeshellarg(\$clientSecret)]", $recipe);
        $this->assertStringNotContainsString('__CLIENT_SECRET__', $recipe);
        $this->assertStringContainsString("run('chmod 700 '.escapeshellarg(\$credentialsDirectory));", $recipe);
        $this->assertStringContainsString("upload(\$temporaryCredentialsFile, \$temporaryCredentialsPath, ['flags' => '-az']);", $recipe);
        $this->assertStringContainsString("run('rm -f '.escapeshellarg(\$temporaryCredentialsPath), nothrow: true);", $recipe);
        $this->assertStringContainsString('Shared Google reporting credentials could not be secured with mode 0600.', $recipe);
        $this->assertStringContainsString("after('rollback', 'deploy:health-check');", $recipe);
        $this->assertStringNotContainsString('app:release-check', $recipe);
        $this->assertStringNotContainsString('operations:check-readiness', $recipe);
        $this->assertStringNotContainsString('app:record-scheduler-heartbeat', $recipe);
    }

    public function test_deployer_manages_and_verifies_one_marked_scheduler_cron_entry_before_the_health_check(): void
    {
        $recipe = file_get_contents(base_path('deploy.php'));

        $this->assertNotFalse($recipe);
        $this->assertStringNotContainsString("require 'contrib/crontab.php';", $recipe);
        $this->assertStringContainsString("set('bin/crontab', fn (): string => which('crontab'));", $recipe);
        $this->assertStringContainsString("set('website:scheduler-cron-identifier', 'ibrahim-website-scheduler');", $recipe);
        $this->assertStringContainsString("task('deploy:sync-scheduler-cron'", $recipe);
        $this->assertStringContainsString("\$currentPath = parse('{{current_path}}');", $recipe);
        $this->assertStringContainsString("\$phpBinary = (string) get('bin/php');", $recipe);
        $this->assertStringContainsString(
            'if (! preg_match(\'~\A/[A-Za-z0-9_@%+=:,.\/-]+\z~\', $binaryOrPath)) {',
            $recipe,
        );
        $this->assertStringContainsString("'website_scheduler_crontab_binary' => escapeshellarg(\$crontabBinary)", $recipe);
        $this->assertStringContainsString('current_path=%website_scheduler_current_path%', $recipe);
        $this->assertStringContainsString('php_binary=%website_scheduler_php_binary%', $recipe);
        $this->assertStringContainsString('if LC_ALL=C "$crontab_binary" -l > "$existing" 2> "$read_error"; then', $recipe);
        $this->assertStringContainsString("grep -Eq '^no crontab for [^[:space:]]+$'", $recipe);
        $this->assertStringContainsString('The existing crontab could not be read safely.', $recipe);
        $this->assertStringContainsString('The managed scheduler section is malformed.', $recipe);
        $this->assertStringContainsString('! inside && $0 != job { print }', $recipe);
        $this->assertStringContainsString('schedule:run --no-interaction >> /dev/null 2>&1', $recipe);
        $this->assertStringContainsString('run($script, cwd: \'\', secrets:', $recipe);
        $this->assertStringContainsString("task('artisan:schedule-run', artisan('schedule:run --no-interaction'));", $recipe);
        $this->assertStringContainsString("after('deploy:symlink', 'deploy:sync-scheduler-cron');", $recipe);
        $this->assertStringContainsString("after('deploy:sync-scheduler-cron', 'artisan:schedule-run');", $recipe);
        $this->assertStringContainsString("after('artisan:schedule-run', 'deploy:health-check');", $recipe);
        $this->assertStringNotContainsString('crontab:sync', $recipe);
        $this->assertStringNotContainsString('crontab -u', $recipe);
        $this->assertStringNotContainsString("after('deploy:symlink', 'deploy:health-check');", $recipe);
    }

    public function test_deployment_plan_documents_reporting_settings_and_deploy_managed_scheduler_behavior(): void
    {
        $plan = file_get_contents(base_path('DEPLOYMENT-PLAN.md'));

        $this->assertNotFalse($plan);

        foreach ([
            'GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON',
            'WEBSITE_METRICS_CLIENT_SECRET',
            'WEBSITE_METRICS_API_CLIENT_ID',
            'WEBSITE_PERFORMANCE_WEBSITE_URL',
        ] as $reportingSetting) {
            $this->assertStringContainsString($reportingSetting, $plan);
        }

        $this->assertStringContainsString('ibrahim-website-scheduler', $plan);
        $this->assertStringContainsString('preserves unrelated crontab entries', $plan);
        $this->assertStringContainsString('before the public health check', $plan);
        $this->assertStringContainsString('Do not add a second manual scheduler cron entry or `schedule:work` process', $plan);
    }

    private function workflow(): string
    {
        $workflow = file_get_contents(base_path('.github/workflows/deploy.yml'));

        $this->assertNotFalse($workflow);

        return $workflow;
    }
}
