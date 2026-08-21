<?php

namespace Deployer;

use JsonException;
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

set('bin/crontab', fn (): string => which('crontab'));
set('website:scheduler-cron-identifier', 'ibrahim-website-scheduler');

host('production')
    ->setHostname((string) getenv('DEPLOY_HOST'))
    ->setRemoteUser((string) (getenv('DEPLOY_USER') ?: 'ibrahim-production'))
    ->setPort((int) (getenv('DEPLOY_PORT') ?: 22))
    ->setDeployPath((string) getenv('DEPLOY_PATH'));

task('deploy:validate-runtime-configuration', function (): void {
    foreach ([
        'DEPLOY_HOST',
        'DEPLOY_USER',
        'DEPLOY_PATH',
        'DEPLOY_HEALTH_URL',
        'GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON',
        'WEBSITE_METRICS_API_CLIENT_ID',
        'WEBSITE_METRICS_CLIENT_SECRET',
        'WEBSITE_PERFORMANCE_WEBSITE_URL',
    ] as $variable) {
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

task('deploy:provision-website-performance-reporting', function (): void {
    $clientId = trim((string) getenv('WEBSITE_METRICS_API_CLIENT_ID'));
    $clientSecret = trim((string) getenv('WEBSITE_METRICS_CLIENT_SECRET'));
    $serviceAccountJson = trim((string) getenv('GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON'));
    $reportingOrigin = trim((string) getenv('WEBSITE_PERFORMANCE_WEBSITE_URL'));

    if (! preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $clientId)) {
        throw new RuntimeException('WEBSITE_METRICS_API_CLIENT_ID must be a UUID.');
    }

    if ($clientSecret === '' || strpbrk($clientSecret, "\r\n") !== false) {
        throw new RuntimeException('WEBSITE_METRICS_CLIENT_SECRET must be a single-line secret.');
    }

    $reportingOriginParts = parse_url($reportingOrigin);

    if ($reportingOriginParts === false
        || ! filter_var($reportingOrigin, FILTER_VALIDATE_URL)
        || ($reportingOriginParts['scheme'] ?? null) !== 'https'
        || ! is_string($reportingOriginParts['host'] ?? null)
        || filter_var($reportingOriginParts['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
        || isset($reportingOriginParts['user'])
        || isset($reportingOriginParts['pass'])
        || isset($reportingOriginParts['query'])
        || isset($reportingOriginParts['fragment'])
        || ! in_array($reportingOriginParts['path'] ?? '', ['', '/'], true)
        || (isset($reportingOriginParts['port']) && (! is_int($reportingOriginParts['port']) || $reportingOriginParts['port'] < 1 || $reportingOriginParts['port'] > 65535))) {
        throw new RuntimeException('WEBSITE_PERFORMANCE_WEBSITE_URL must be a valid HTTPS canonical reporting origin.');
    }

    $websiteOrigin = 'https://'.strtolower($reportingOriginParts['host'])
        .(isset($reportingOriginParts['port']) && $reportingOriginParts['port'] !== 443 ? ':'.$reportingOriginParts['port'] : '');
    $metricsUrl = "$websiteOrigin/api/v1/metrics/website";
    $metricsTokenUrl = "$websiteOrigin/oauth/token";

    try {
        $serviceAccount = json_decode($serviceAccountJson, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new RuntimeException('GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON must be valid service-account JSON.');
    }

    if (! is_array($serviceAccount)
        || ($serviceAccount['type'] ?? null) !== 'service_account'
        || ! is_string($serviceAccount['client_email'] ?? null)
        || filter_var($serviceAccount['client_email'], FILTER_VALIDATE_EMAIL) === false
        || ! is_string($serviceAccount['private_key'] ?? null)
        || ! str_starts_with($serviceAccount['private_key'], '-----BEGIN PRIVATE KEY-----')) {
        throw new RuntimeException('GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON must contain valid service-account credentials.');
    }

    $environmentFile = '{{deploy_path}}/shared/.env';
    $credentialsDirectory = '{{deploy_path}}/shared/storage/app/private/google';
    $credentialsPath = "$credentialsDirectory/reporting-service-account.json";
    $temporaryCredentialsPath = "$credentialsDirectory/.reporting-service-account-".bin2hex(random_bytes(16));
    $temporaryCredentialsFile = tempnam(sys_get_temp_dir(), 'website-performance-google-');

    if ($temporaryCredentialsFile === false) {
        throw new RuntimeException('Google reporting credentials could not be prepared for deployment.');
    }

    $temporaryCredentialsMayExist = false;

    try {
        if (file_put_contents($temporaryCredentialsFile, $serviceAccountJson, LOCK_EX) === false
            || ! chmod($temporaryCredentialsFile, 0600)) {
            throw new RuntimeException('Google reporting credentials could not be prepared for deployment.');
        }

        run('mkdir -p '.escapeshellarg($credentialsDirectory));
        run('chmod 700 '.escapeshellarg($credentialsDirectory));
        $temporaryCredentialsMayExist = true;
        upload($temporaryCredentialsFile, $temporaryCredentialsPath, ['flags' => '-az']);

        $credentialsScript = <<<'BASH'
set -eu
temporary_path=__TEMPORARY_CREDENTIALS_PATH__
credentials_path=__CREDENTIALS_PATH__
trap 'rm -f "$temporary_path"' EXIT

chmod 600 "$temporary_path"
mv "$temporary_path" "$credentials_path"
chmod 600 "$credentials_path"
trap - EXIT
BASH;

        run(str_replace(
            ['__TEMPORARY_CREDENTIALS_PATH__', '__CREDENTIALS_PATH__'],
            [escapeshellarg($temporaryCredentialsPath), escapeshellarg($credentialsPath)],
            $credentialsScript,
        ));
        $temporaryCredentialsMayExist = false;

        $script = <<<'BASH'
set -eu
environment_file=__ENVIRONMENT_FILE__

if [ ! -f "$environment_file" ]; then
  printf '%s\n' 'The shared production environment file is missing.' >&2
  exit 1
fi

temporary_file="$(mktemp "${environment_file}.website-metrics-client.XXXXXX")"
trap 'rm -f "$temporary_file"' EXIT

sed '/^APP_URL=/d; /^GOOGLE_REPORTING_CREDENTIALS_PATH=/d; /^WEBSITE_PERFORMANCE_WEBSITE_URL=/d; /^WEBSITE_METRICS_API_URL=/d; /^WEBSITE_METRICS_TOKEN_URL=/d; /^WEBSITE_METRICS_API_CLIENT_ID=/d; /^WEBSITE_METRICS_CLIENT_ID=/d; /^WEBSITE_METRICS_CLIENT_SECRET=/d' "$environment_file" > "$temporary_file"
printf '\nAPP_URL=%s\n' __WEBSITE_ORIGIN__ >> "$temporary_file"
printf '\nGOOGLE_REPORTING_CREDENTIALS_PATH=%s\n' __CREDENTIALS_PATH__ >> "$temporary_file"
printf 'WEBSITE_PERFORMANCE_WEBSITE_URL=%s\n' __WEBSITE_ORIGIN__ >> "$temporary_file"
printf 'WEBSITE_METRICS_API_URL=%s\n' __METRICS_URL__ >> "$temporary_file"
printf 'WEBSITE_METRICS_TOKEN_URL=%s\n' __METRICS_TOKEN_URL__ >> "$temporary_file"
printf '\nWEBSITE_METRICS_API_CLIENT_ID=%s\n' __CLIENT_ID__ >> "$temporary_file"
printf 'WEBSITE_METRICS_CLIENT_ID=%s\n' __CLIENT_ID__ >> "$temporary_file"
printf 'WEBSITE_METRICS_CLIENT_SECRET=%s\n' %website_metrics_client_secret% >> "$temporary_file"
chmod 600 "$temporary_file"
mv "$temporary_file" "$environment_file"
trap - EXIT
BASH;

        run(str_replace(
            [
                '__ENVIRONMENT_FILE__',
                '__CREDENTIALS_PATH__',
                '__WEBSITE_ORIGIN__',
                '__METRICS_URL__',
                '__METRICS_TOKEN_URL__',
                '__CLIENT_ID__',
            ],
            [
                escapeshellarg($environmentFile),
                escapeshellarg($credentialsPath),
                escapeshellarg($websiteOrigin),
                escapeshellarg($metricsUrl),
                escapeshellarg($metricsTokenUrl),
                escapeshellarg($clientId),
            ],
            $script,
        ), secrets: ['website_metrics_client_secret' => escapeshellarg($clientSecret)]);

        if (! test("[ -s $credentialsPath ]")) {
            throw new RuntimeException('Shared Google reporting credentials are missing. Provision them through the secret manager before deploying.');
        }

        if (trim(run("stat -c '%a' $credentialsPath")) !== '600') {
            throw new RuntimeException('Shared Google reporting credentials could not be secured with mode 0600.');
        }
    } finally {
        if ($temporaryCredentialsMayExist) {
            run('rm -f '.escapeshellarg($temporaryCredentialsPath), nothrow: true);
        }

        if (is_file($temporaryCredentialsFile)) {
            unlink($temporaryCredentialsFile);
        }
    }
});

task('artisan:filament-optimize', artisan('filament:optimize'));
task('artisan:event-cache', artisan('event:cache'));
task('artisan:view-cache', artisan('view:cache'));
task('artisan:horizon-terminate', artisan('horizon:terminate'));
task('artisan:schedule-interrupt', artisan('schedule:interrupt'));
task('artisan:schedule-run', artisan('schedule:run --no-interaction'));
task('artisan:sanitize-website-performance-snapshots', artisan('website:performance-sanitize-snapshots --no-interaction'));

task('deploy:sync-scheduler-cron', function (): void {
    $identifier = (string) get('website:scheduler-cron-identifier');
    $currentPath = parse('{{current_path}}');
    $phpBinary = (string) get('bin/php');
    $crontabBinary = (string) get('bin/crontab');

    foreach ([$currentPath, $phpBinary, $crontabBinary] as $binaryOrPath) {
        if (! preg_match('~\A/[A-Za-z0-9_@%+=:,.\/-]+\z~', $binaryOrPath)) {
            throw new RuntimeException('The scheduler runner requires absolute paths containing only supported characters.');
        }
    }

    $script = <<<'BASH'
set -eu
umask 077
current_path=%website_scheduler_current_path%
php_binary=%website_scheduler_php_binary%
crontab_binary=%website_scheduler_crontab_binary%
marker_start=%website_scheduler_marker_start%
marker_end=%website_scheduler_marker_end%
work_directory="$(mktemp -d /tmp/ibrahim-website-scheduler.XXXXXX)"
existing="$work_directory/existing"
candidate="$work_directory/candidate"
installed="$work_directory/installed"
read_error="$work_directory/read-error"

cleanup() {
    rm -rf "$work_directory"
}

fail() {
    printf '%s\n' "$1" >&2
    exit 1
}

trap cleanup EXIT HUP INT TERM

[ -x "$crontab_binary" ] || fail 'The deployment user cannot access crontab.'
[ -x "$php_binary" ] || fail 'The configured PHP binary is not executable.'
[ -r "$current_path/artisan" ] || fail 'The current release is missing Artisan.'

if LC_ALL=C "$crontab_binary" -l > "$existing" 2> "$read_error"; then
    :
elif [ "$(wc -l < "$read_error")" -eq 1 ] && grep -Eq '^no crontab for [^[:space:]]+$' "$read_error"; then
    : > "$existing"
else
    fail 'The existing crontab could not be read safely.'
fi

cron_job="* * * * * cd $current_path && $php_binary artisan schedule:run --no-interaction >> /dev/null 2>&1"

if ! awk -v start="$marker_start" -v end="$marker_end" -v job="$cron_job" '
    $0 == start {
        if (inside || ++start_count > 1) exit 1
        inside = 1
        next
    }
    $0 == end {
        if (! inside || ++end_count > 1) exit 1
        inside = 0
        next
    }
    ! inside && $0 != job { print }
    END {
        if (inside || start_count != end_count) exit 1
    }
' "$existing" > "$candidate" 2> /dev/null; then
    fail 'The managed scheduler section is malformed.'
fi

printf '%s\n%s\n%s\n' "$marker_start" "$cron_job" "$marker_end" >> "$candidate"

"$crontab_binary" "$candidate" > /dev/null 2>&1 || fail 'The scheduler crontab could not be installed.'
LC_ALL=C "$crontab_binary" -l > "$installed" 2> /dev/null || fail 'The scheduler crontab could not be verified.'

[ "$(grep -Fxc -- "$marker_start" "$installed" || true)" -eq 1 ] || fail 'The scheduler marker was not installed exactly once.'
[ "$(grep -Fxc -- "$marker_end" "$installed" || true)" -eq 1 ] || fail 'The scheduler marker was not installed exactly once.'
[ "$(grep -Fxc -- "$cron_job" "$installed" || true)" -eq 1 ] || fail 'The scheduler job was not installed exactly once.'
BASH;

    run($script, cwd: '', secrets: [
        'website_scheduler_current_path' => escapeshellarg($currentPath),
        'website_scheduler_php_binary' => escapeshellarg($phpBinary),
        'website_scheduler_crontab_binary' => escapeshellarg($crontabBinary),
        'website_scheduler_marker_start' => escapeshellarg("###< $identifier"),
        'website_scheduler_marker_end' => escapeshellarg("###> $identifier"),
    ]);
});

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
after('deploy:writable', 'deploy:provision-website-performance-reporting');
before('artisan:migrate', 'artisan:assert-passport-keys');
before('deploy:symlink', 'artisan:sanitize-website-performance-snapshots');
before('deploy:symlink', 'deploy:prepare-application');

after('deploy:symlink', 'artisan:horizon-terminate');
after('deploy:symlink', 'artisan:schedule-interrupt');
after('deploy:symlink', 'deploy:sync-scheduler-cron');
after('deploy:sync-scheduler-cron', 'artisan:schedule-run');
after('artisan:schedule-run', 'deploy:health-check');

before('rollback', 'deploy:validate-runtime-configuration');
before('rollback', 'artisan:assert-passport-keys');
after('rollback', 'artisan:horizon-terminate');
after('rollback', 'artisan:schedule-interrupt');
after('rollback', 'deploy:health-check');
