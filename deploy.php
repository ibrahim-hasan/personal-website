<?php

namespace Deployer;

use RuntimeException;

require 'recipe/laravel.php';

set('application', 'ibrahim-website');
set('repository', 'git@github.com:ibrahim-hasan/personal-website.git');
set('keep_releases', 5);
set('php_fpm_version', '8.4');
set('branch', 'production');
set('git_ssh_command', 'ssh -o BatchMode=yes -o StrictHostKeyChecking=yes');
set('writable_mode', 'chmod');

set('shared_files', ['.env']);
set('shared_dirs', ['storage']);
set('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app/private',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);

host('production')
    ->setHostname((string) getenv('DEPLOY_HOST'))
    ->setRemoteUser((string) (getenv('DEPLOY_USER') ?: 'ibrahim-production'))
    ->setPort((int) (getenv('DEPLOY_PORT') ?: 22))
    ->setDeployPath((string) getenv('DEPLOY_PATH'));

task('deploy:validate-runtime-configuration', function (): void {
    foreach (['DEPLOY_HOST', 'DEPLOY_USER', 'DEPLOY_PATH', 'DEPLOY_HEALTH_URL', 'DEPLOY_READINESS_URL'] as $variable) {
        if (trim((string) getenv($variable)) === '') {
            throw new RuntimeException("$variable must be configured before deployment.");
        }
    }
});

task('deploy:assert-explicit-revision', function (): void {
    $revision = trim((string) input()->getOption('revision'));

    if (! preg_match('/\A[0-9a-f]{40}\z/i', $revision)) {
        throw new RuntimeException('Deployments require a full 40-character Git revision.');
    }
});

task('deploy:upload-build', function (): void {
    if (! testLocally('[ -f public/build/manifest.json ]')) {
        throw new RuntimeException('The immutable frontend artifact is missing its Vite manifest.');
    }

    upload('public/build/', '{{release_path}}/public/build/');
});

task('artisan:filament-optimize', artisan('filament:optimize'));
task('artisan:event-cache', artisan('event:cache'));
task('artisan:view-cache', artisan('view:cache'));
task('artisan:horizon-terminate', artisan('horizon:terminate'));
task('artisan:schedule-interrupt', artisan('schedule:interrupt'));
task('artisan:record-scheduler-heartbeat', artisan('app:record-scheduler-heartbeat --no-interaction'));
task('artisan:release-check', artisan('app:release-check --no-interaction', ['showOutput']));

task('artisan:assert-passport-keys', function (): void {
    foreach ([
        '{{deploy_path}}/shared/storage/oauth-private.key',
        '{{deploy_path}}/shared/storage/oauth-public.key',
    ] as $keyPath) {
        if (! test("[ -s $keyPath ]")) {
            throw new RuntimeException('Shared Passport key material is missing. Provision it through the secret manager before deploying.');
        }

        if (trim(run("stat -c '%a' $keyPath")) !== '600') {
            throw new RuntimeException('Shared Passport key material must use mode 0600 before deploying.');
        }
    }
});

task('deploy:health-check', function (): void {
    $healthUrl = trim((string) getenv('DEPLOY_HEALTH_URL'));

    if ($healthUrl === '') {
        throw new RuntimeException('DEPLOY_HEALTH_URL must be configured before deployment.');
    }

    run('curl --fail --silent --show-error --max-time 20 '.escapeshellarg($healthUrl));
});

task('deploy:readiness-check', function (): void {
    $readinessUrl = trim((string) getenv('DEPLOY_READINESS_URL'));

    if ($readinessUrl === '') {
        throw new RuntimeException('DEPLOY_READINESS_URL must be configured before deployment.');
    }

    run('{{bin/php}} {{bin/artisan}} operations:check-readiness --url='.escapeshellarg($readinessUrl).' --no-interaction');
});

task('deploy:prepare-application', [
    'deploy:upload-build',
    'artisan:filament-optimize',
    'artisan:event-cache',
    'artisan:view-cache',
]);

after('deploy:failed', 'deploy:unlock');

before('deploy:prepare', 'deploy:validate-runtime-configuration');
before('deploy:prepare', 'deploy:assert-explicit-revision');
before('artisan:migrate', 'artisan:assert-passport-keys');
before('deploy:symlink', 'deploy:prepare-application');

after('deploy:symlink', 'artisan:horizon-terminate');
after('deploy:symlink', 'artisan:schedule-interrupt');
after('deploy:symlink', 'artisan:record-scheduler-heartbeat');
after('deploy:symlink', 'artisan:release-check');
after('deploy:symlink', 'deploy:health-check');
after('deploy:symlink', 'deploy:readiness-check');

before('rollback', 'deploy:validate-runtime-configuration');
before('rollback', 'artisan:assert-passport-keys');
after('rollback', 'artisan:horizon-terminate');
after('rollback', 'artisan:schedule-interrupt');
after('rollback', 'artisan:record-scheduler-heartbeat');
after('rollback', 'artisan:release-check');
after('rollback', 'deploy:health-check');
after('rollback', 'deploy:readiness-check');
