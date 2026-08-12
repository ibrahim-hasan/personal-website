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
    foreach (['DEPLOY_HOST', 'DEPLOY_USER', 'DEPLOY_PATH', 'DEPLOY_HEALTH_URL', 'WEBSITE_METRICS_API_CLIENT_ID'] as $variable) {
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

task('deploy:configure-website-metrics-client', function (): void {
    $clientId = trim((string) getenv('WEBSITE_METRICS_API_CLIENT_ID'));

    if (! preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $clientId)) {
        throw new RuntimeException('WEBSITE_METRICS_API_CLIENT_ID must be a UUID.');
    }

    $environmentFile = '{{deploy_path}}/shared/.env';

    run(sprintf(<<<'BASH'
set -eu
environment_file=%s

if [ ! -f "$environment_file" ]; then
  printf '%s\n' 'The shared production environment file is missing.' >&2
  exit 1
fi

temporary_file="$(mktemp "${environment_file}.website-metrics-client.XXXXXX")"
trap 'rm -f "$temporary_file"' EXIT

sed '/^WEBSITE_METRICS_API_CLIENT_ID=/d' "$environment_file" > "$temporary_file"
printf '\nWEBSITE_METRICS_API_CLIENT_ID=%%s\n' %s >> "$temporary_file"
chmod 600 "$temporary_file"
mv "$temporary_file" "$environment_file"
trap - EXIT
BASH, escapeshellarg($environmentFile), escapeshellarg($clientId)));
});

task('artisan:filament-optimize', artisan('filament:optimize'));
task('artisan:event-cache', artisan('event:cache'));
task('artisan:view-cache', artisan('view:cache'));
task('artisan:horizon-terminate', artisan('horizon:terminate'));
task('artisan:schedule-interrupt', artisan('schedule:interrupt'));

task('artisan:assert-passport-keys', function (): void {
    foreach ([
        '{{deploy_path}}/shared/storage/oauth-private.key',
        '{{deploy_path}}/shared/storage/oauth-public.key',
    ] as $keyPath) {
        if (! test("[ -s $keyPath ]")) {
            throw new RuntimeException('Shared Passport key material is missing. Provision it through the secret manager before deploying.');
        }

        run("chmod 600 $keyPath");

        if (trim(run("stat -c '%a' $keyPath")) !== '600') {
            throw new RuntimeException('Shared Passport key material could not be secured with mode 0600.');
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

task('deploy:prepare-application', [
    'deploy:upload-build',
    'artisan:filament-optimize',
    'artisan:event-cache',
    'artisan:view-cache',
]);

after('deploy:failed', 'deploy:unlock');

before('deploy:prepare', 'deploy:validate-runtime-configuration');
before('deploy:prepare', 'deploy:assert-explicit-revision');
after('deploy:shared', 'deploy:configure-website-metrics-client');
before('artisan:migrate', 'artisan:assert-passport-keys');
before('deploy:symlink', 'deploy:prepare-application');

after('deploy:symlink', 'artisan:horizon-terminate');
after('deploy:symlink', 'artisan:schedule-interrupt');
after('deploy:symlink', 'deploy:health-check');

before('rollback', 'deploy:validate-runtime-configuration');
before('rollback', 'artisan:assert-passport-keys');
after('rollback', 'artisan:horizon-terminate');
after('rollback', 'artisan:schedule-interrupt');
after('rollback', 'deploy:health-check');
