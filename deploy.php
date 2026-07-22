<?php

namespace Deployer;

require 'recipe/laravel.php';

set('application', 'ibrahim-website');
set('repository', 'git@github.com:ibrahim-hasan/personal-website.git');
set('keep_releases', 5);
set('php_fpm_version', '8.4');
set('branch', 'production');
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
    ->setDeployPath((string) (getenv('DEPLOY_PATH') ?: '/home/ibrahim-production/htdocs/ibrahimhasan.net'));

task('deploy:upload-build', function (): void {
    upload('public/build/', '{{release_path}}/public/build/');
});

task('artisan:filament-optimize', artisan('filament:optimize'));
task('artisan:event-cache', artisan('event:cache'));
task('artisan:view-cache', artisan('view:cache'));
task('artisan:horizon-terminate', artisan('horizon:terminate'));
task('artisan:schedule-interrupt', artisan('schedule:interrupt'));

task('artisan:passport-keys', function (): void {
    if (! test('[ -f {{deploy_path}}/shared/storage/oauth-private.key ] && [ -f {{deploy_path}}/shared/storage/oauth-public.key ]')) {
        run('cd {{release_path}} && {{bin/php}} artisan passport:keys --force --no-interaction');
    }

    run('chmod 600 {{deploy_path}}/shared/storage/oauth-private.key');
    run('chmod 600 {{deploy_path}}/shared/storage/oauth-public.key');
});

task('deploy:health-check', function (): void {
    $healthUrl = trim((string) getenv('DEPLOY_HEALTH_URL'));

    if ($healthUrl === '') {
        warning('DEPLOY_HEALTH_URL is not configured; skipping external health check until DNS is ready.');

        return;
    }

    run('curl --fail --silent --show-error --max-time 20 '.escapeshellarg($healthUrl));
});

after('deploy:failed', 'deploy:unlock');

before('deploy:symlink', 'deploy:upload-build');
before('deploy:symlink', 'artisan:filament-optimize');
before('deploy:symlink', 'artisan:event-cache');
before('deploy:symlink', 'artisan:view-cache');
before('deploy:symlink', 'artisan:passport-keys');

after('deploy:symlink', 'artisan:horizon-terminate');
after('deploy:symlink', 'artisan:schedule-interrupt');
after('deploy:symlink', 'deploy:health-check');
