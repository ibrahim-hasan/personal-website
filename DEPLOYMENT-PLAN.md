# Ibrahim Hasan Website — Production Deployment Plan

> Adapted from the proven Jisr CloudPanel + Deployer + GitHub Actions deployment model for this repository's actual architecture.
>
> Scope: **one production environment only** at `https://ibrahimhasan.net`, deployed to CloudPanel over SSH, with public dynamic media stored in Cloudflare R2.

## 1. Current readiness and decisions

| Area | Decision / current state |
|---|---|
| Repository | `git@github.com:ibrahim-hasan/personal-website.git` |
| Production branch | `production` (recommended protected deployment branch) |
| Application domain | `ibrahimhasan.net` |
| Canonical host | `https://ibrahimhasan.net` |
| `www` behavior | Redirect permanently to the canonical apex domain |
| Server | Existing CloudPanel server; verify its IP before DNS changes |
| PHP | 8.4 |
| Web root | `/home/ibrahim-production/htdocs/ibrahimhasan.net/current/public` |
| Deployment | Deployer 8, triggered by GitHub Actions |
| Build | Composer production install + Node 22/Vite build in CI |
| Database | MySQL 8 on the CloudPanel server |
| Queue | Laravel database queue managed by Supervisor; no Horizon |
| Scheduler | One system cron entry for `schedule:run`; no app schedules are currently registered |
| Public dynamic media | Cloudflare R2 through the Laravel `s3` disk after the code-readiness gates below |
| Private application files | Local shared `storage/app/private` |
| Releases retained | 5 |

### Required changes before R2 can work

The repository configures an `s3` filesystem disk but does **not** currently install Laravel's S3 Flysystem adapter. Before enabling R2, approve and add:

```bash
composer require league/flysystem-aws-s3-v3 "^3.0" --with-all-dependencies
```

Commit the resulting `composer.json` and `composer.lock` changes before the first production deployment. Do not test R2 by manually editing production `vendor/` files.

The Article and Project media collections also currently call `useDisk('public')`, which overrides the global `MEDIA_DISK` setting. Before enabling R2:

1. Refactor those collections to use a dedicated configurable media disk.
2. Add tests for original uploads, generated conversions, public URLs, replacement, and deletion on that disk.
3. Plan and verify migration of every existing Media Library object from the local public disk to R2.
4. Keep `MEDIA_DISK=public` in production until the code change, object migration, and read-back audit all pass.

Article audio already reads `ELEVENLABS_AUDIO_DISK`, but it still requires the S3 adapter before `s3` can be selected.

### Intentionally not copied from Jisr

- No staging host, staging database, or staging workflow.
- No Laravel Horizon: this project does not install or configure Horizon.
- No Jisr users, paths, domains, database names, Redis prefixes, deploy keys, or secrets.
- No automatic database seeding on every production release. Production content must not be overwritten by seeders.
- No assumption that the Jisr origin IP is also this site's origin IP; verify it in CloudPanel first.

## 2. Target production layout

Use a dedicated CloudPanel site user so this application is isolated from other applications on the same server.

```text
/home/ibrahim-production/htdocs/ibrahimhasan.net/
├── .env                       # shared production environment file
├── current -> releases/<id>  # active release symlink
├── releases/                  # immutable releases; keep 5
└── shared/
    └── storage/
        ├── app/private/
        ├── framework/
        └── logs/
```

Recommended CloudPanel names:

- Site user: `ibrahim-production`
- Database: `ibrahim_production`
- Database user: `ibrahim_prod_user`
- Site root: `/home/ibrahim-production/htdocs/ibrahimhasan.net/current/public`

Generate passwords in CloudPanel. Never place real credentials in this file, GitHub Actions logs, repository variables, or committed `.env` files.

## 3. CloudPanel preparation

### 3.1 Create the PHP site

In CloudPanel:

1. Create a PHP site for `ibrahimhasan.net`.
2. Select PHP 8.4.
3. Use the dedicated site user `ibrahim-production`.
4. Initially point the root at the site's normal `htdocs` directory.
5. After the first successful Deployer release, change the document root to:

```text
/home/ibrahim-production/htdocs/ibrahimhasan.net/current/public
```

6. Configure a permanent redirect from `www.ibrahimhasan.net` to `https://ibrahimhasan.net$request_uri`.

Required PHP extensions include the Laravel/Filament baseline plus media and audio processing requirements:

```text
bcmath, ctype, curl, dom, fileinfo, gd, intl, mbstring, mysql, openssl,
pdo, pdo_mysql, sodium, tokenizer, xml, zip
```

Install `ffmpeg` and `ffprobe` on the server because article audio processing and media-library conversions reference them. Confirm the binaries are visible to the site user:

```bash
sudo -u ibrahim-production ffmpeg -version
sudo -u ibrahim-production ffprobe -version
```

### 3.2 Create the database

Create the production database and user in CloudPanel. Restrict the user to the production database only. Record the generated values in the server-side `.env` after the first release directory exists.

### 3.3 SSH access

Use two distinct trust directions:

1. **GitHub Actions → production server**: a dedicated deploy SSH key stored as a GitHub Environment secret.
2. **Production server → GitHub repository**: a read-only GitHub deploy key for cloning the private repository.

Do not reuse a personal SSH key. Do not give the repository deploy key write access.

## 4. Cloudflare DNS, TLS, and R2

### 4.1 DNS and TLS rollout

1. Confirm the production server's public IP in CloudPanel.
2. Add an `A` record for `@` pointing to that verified IP.
3. Add `www` and redirect it to the apex domain using Cloudflare Redirect Rules or the origin.
4. Keep records **DNS only** until the origin certificate is issued and direct HTTPS is verified.
5. Issue the certificate in CloudPanel for both apex and `www`.
6. Verify the origin directly, then enable the Cloudflare proxy.
7. Set Cloudflare SSL/TLS mode to **Full (strict)**.
8. Only enable HSTS after the canonical HTTPS host and redirect have been validated.

### 4.2 Create the R2 bucket

Recommended production resources:

- Bucket: `ibrahimhasan-production-media`
- Public custom domain: `media.ibrahimhasan.net`
- API token: Object Read & Write, restricted to this bucket only
- Public development URL (`r2.dev`): disabled after validation

Cloudflare R2 is S3-compatible. Use the account endpoint:

```text
https://<CLOUDFLARE_ACCOUNT_ID>.r2.cloudflarestorage.com
```

If a jurisdiction-specific bucket is selected, use its jurisdiction endpoint instead. Do not guess the endpoint.

Connect `media.ibrahimhasan.net` from the bucket's **Custom Domains** settings. This is the production delivery URL and allows Cloudflare caching; do not CNAME to an `r2.dev` URL.

### 4.3 Eventual R2 filesystem policy for this application

Keep private framework/application files local and move only publicly delivered dynamic media to R2:

```dotenv
FILESYSTEM_DISK=local
MEDIA_DISK=s3
ELEVENLABS_AUDIO_DISK=s3

AWS_ACCESS_KEY_ID=<R2_ACCESS_KEY_ID>
AWS_SECRET_ACCESS_KEY=<R2_SECRET_ACCESS_KEY>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=ibrahimhasan-production-media
AWS_ENDPOINT=https://<CLOUDFLARE_ACCOUNT_ID>.r2.cloudflarestorage.com
AWS_URL=https://media.ibrahimhasan.net
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Use these values only after the media-collection code and existing-object migration gates above have passed. They then keep Spatie Media Library project/article images and generated article audio durable across releases. Local `storage/app/private`, framework caches, and logs remain in Deployer's shared storage.

Before switching the production disks, verify upload, conversion, read, delete, and generated public URLs from a temporary test record. Then remove the test object.

## 5. Production environment

Create `/home/ibrahim-production/htdocs/ibrahimhasan.net/.env` with mode `600`, owned by the site user. The following is a template only:

```dotenv
APP_NAME="Ibrahim Hasan"
APP_ENV=production
APP_KEY=<GENERATED_ONCE_AND_PRESERVED>
APP_DEBUG=false
APP_URL=https://ibrahimhasan.net
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=ar_SA

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ibrahim-production
DB_USERNAME=ibrahim-prod-user
DB_PASSWORD=<SECRET>Yd8ZvslfxE8Ei9cyOqkY

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=1800
QUEUE_FAILED_DRIVER=database-uuids

FILESYSTEM_DISK=local
MEDIA_DISK=s3
ELEVENLABS_AUDIO_DISK=s3
AWS_ACCESS_KEY_ID=<R2_ACCESS_KEY_ID>
AWS_SECRET_ACCESS_KEY=<R2_SECRET_ACCESS_KEY>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=ibrahimhasan-production-media
AWS_ENDPOINT=https://<CLOUDFLARE_ACCOUNT_ID>.r2.cloudflarestorage.com
AWS_URL=https://media.ibrahimhasan.net
AWS_USE_PATH_STYLE_ENDPOINT=false

MAIL_MAILER=smtp
MAIL_HOST=<SMTP_HOST>
MAIL_PORT=587
MAIL_USERNAME=<SMTP_USERNAME>
MAIL_PASSWORD=<SMTP_PASSWORD>
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=hello@ibrahimhasan.net
MAIL_FROM_NAME="${APP_NAME}"

GOOGLE_ANALYTICS_MEASUREMENT_ID=G-L305M0T213

OPENAI_API_KEY=<SECRET>
OPENAI_QUEUE_CONNECTION=database
OPENAI_QUEUE=article-audio

ELEVENLABS_API_KEY=<SECRET>
ELEVENLABS_VOICE_ID=<VOICE_ID>
ELEVENLABS_QUEUE_CONNECTION=database
ELEVENLABS_QUEUE=article-audio
ELEVENLABS_TIMEOUT=150
ELEVENLABS_CONNECT_TIMEOUT=15
ELEVENLABS_JOB_TIMEOUT=1560
ELEVENLABS_UNIQUE_FOR=1800
ELEVENLABS_FFMPEG_BINARY=/usr/bin/ffmpeg
ELEVENLABS_FFPROBE_BINARY=/usr/bin/ffprobe

N8N_LOG_WEBHOOK_URL=<OPTIONAL_SECRET_URL>
N8N_LOG_WEBHOOK_TOKEN=<OPTIONAL_SECRET>
N8N_LOG_PROJECT="Ibrahim Hasan Production"
N8N_LOG_LEVEL=warning
N8N_LOG_TIMEOUT=3
```

Generate `APP_KEY` once with `php artisan key:generate --show`, store it in the shared `.env`, and preserve it across every release and rollback. Replacing it invalidates encrypted data and sessions.

## 6. Deployment automation

### 6.1 GitHub Environment

Create a GitHub Environment named `production` with deployment protection for the `production` branch. Store:

**Secrets**

- `DEPLOY_SSH_PRIVATE_KEY`
- `DEPLOY_HOST`
- `DEPLOY_USER` (`ibrahim-production`)
- `DEPLOY_PORT` (normally `22`)
- `DEPLOY_KNOWN_HOSTS` (preferred over runtime key scanning)

**Variable**

- `DEPLOY_PATH=/home/ibrahim-production/htdocs/ibrahimhasan.net`

Do not store the application `.env` in GitHub. Application and R2 secrets remain only on the production server.

### 6.2 Recommended release workflow

The Jisr approach should be retained with these Ibrahim-specific phases:

1. Run the focused PHPUnit suite and lint checks.
2. Install Composer dependencies with development tools available for CI validation.
3. Build frontend assets with Node 22 and `npm ci && npm run build`.
4. Install production Composer dependencies for the artifact (`--no-dev --classmap-authoritative`).
5. Connect to the production server using pinned known hosts.
6. Create a new Deployer release.
7. Upload the prebuilt `public/build` artifact.
8. Link shared `.env` and `storage`.
9. Run `php artisan migrate --force`.
10. Run Laravel production caches.
11. Atomically switch `current` to the new release.
12. Run `php artisan queue:restart` so Supervisor workers load the new code.
13. Verify the health endpoint and canonical pages.
14. Keep the last five releases for rollback.

Do **not** run `db:seed --force` on every release. Seed only explicitly reviewed, idempotent reference data when required.

### 6.3 Deployer values

When `deploy.php` is implemented, use these target values:

```php
set('application', 'ibrahim-website');
set('repository', 'git@github.com:ibrahim-hasan/personal-website.git');
set('keep_releases', 5);
set('php_fpm_version', '8.4');

host('production')
    ->setHostname(getenv('DEPLOY_HOST'))
    ->setRemoteUser(getenv('DEPLOY_USER') ?: 'ibrahim-production')
    ->setPort((int) (getenv('DEPLOY_PORT') ?: 22))
    ->setDeployPath(getenv('DEPLOY_PATH') ?: '/home/ibrahim-production/htdocs/ibrahimhasan.net')
    ->set('branch', 'production');
```

Shared assets:

```php
add('shared_files', ['.env']);
add('shared_dirs', ['storage']);
add('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app/private',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);
```

Production optimization tasks:

```bash
php artisan optimize
php artisan filament:optimize
php artisan event:cache
php artisan view:cache
php artisan storage:link
```

`storage:link` remains useful for any legacy/local public files, even though new Media Library and article-audio assets use R2.

## 7. Queue worker and scheduler

### 7.1 Supervisor worker

This application has queued mail/notifications plus long-running OpenAI and ElevenLabs article-audio jobs. The repository default is `retry_after=1620`, while the longest configured job timeout is `1560`. That is valid but leaves only a 60-second margin; use `1800` in production and keep the worker timeout at `1560`.

Create `/etc/supervisor/conf.d/ibrahim-production-worker.conf`:

```ini
[program:ibrahim-production-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php8.4 /home/ibrahim-production/htdocs/ibrahimhasan.net/current/artisan queue:work database --queue=article-audio,default --sleep=3 --tries=1 --timeout=1560 --max-time=3600
directory=/home/ibrahim-production/htdocs/ibrahimhasan.net/current
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=ibrahim-production
numprocs=1
redirect_stderr=true
stdout_logfile=/home/ibrahim-production/htdocs/ibrahimhasan.net/shared/storage/logs/worker.log
stopwaitsecs=1800
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status ibrahim-production-worker:*
```

Start with one process because audio jobs are CPU/network intensive. Increase only after monitoring memory, CPU, API rate limits, and duplicate-generation behavior.

### 7.2 Scheduler cron

Install the standard scheduler entry now so future scheduled maintenance does not require another server change:

```cron
* * * * * cd /home/ibrahim-production/htdocs/ibrahimhasan.net/current && /usr/bin/php8.4 artisan schedule:run >> /dev/null 2>&1
```

At the time this plan was prepared, `routes/console.php` registered no scheduled application tasks. Confirm with `php artisan schedule:list` after each scheduling change.

## 8. First production deployment

### Before changing DNS

- [ ] Confirm the correct CloudPanel server IP and SSH port.
- [ ] Create the dedicated CloudPanel user/site/database.
- [ ] Add the restricted GitHub repository deploy key to the server user.
- [ ] Add the GitHub Environment deploy key and pinned known-host entry.
- [ ] Install and commit the S3 Flysystem adapter.
- [ ] Create the R2 bucket, restricted token, and `media.ibrahimhasan.net` custom domain.
- [ ] Create the shared production `.env` with mode `600`.
- [ ] Confirm PHP 8.4, Composer, Node build artifact, `ffmpeg`, and `ffprobe` requirements.
- [ ] Take a fresh database backup immediately before the first migration.

### First release

1. Merge reviewed changes into the protected `production` branch.
2. Run the GitHub Actions deployment manually for the first release.
3. Confirm `current` points to a complete release.
4. Set the CloudPanel document root to `current/public`.
5. Run migrations once through the deployment workflow.
6. Start Supervisor and confirm the worker remains `RUNNING`.
7. Install the scheduler cron.
8. Verify the origin over HTTPS before proxying it through Cloudflare.
9. Switch DNS/proxy, then run the acceptance checklist.

## 9. Acceptance checklist

### Application

- [ ] `https://ibrahimhasan.net` returns `200` with `APP_DEBUG=false`.
- [ ] `http://`, `www`, and non-canonical localized URLs resolve through intentional redirects without loops.
- [ ] Arabic and English localized routes, translated slugs, canonical URLs, `hreflang`, sitemap, and robots output are correct.
- [ ] `/admin/login` loads and the production admin user can authenticate.
- [ ] CSRF-protected forms, consultation requests, comments, reader registration/login, password reset, profile, reading list, and account deletion work.
- [ ] SMTP delivers consultation and reader notification emails with the correct production sender.
- [ ] Google Analytics is present only in production.

### Storage and media

- [ ] A new Filament image upload writes to R2 and is served from `media.ibrahimhasan.net`.
- [ ] Spatie conversions complete and their URLs persist across a second deployment.
- [ ] Article audio writes to R2, plays with the correct MIME type, supports range requests, and persists across releases.
- [ ] Object deletion from the admin removes the intended R2 object and no unrelated keys.
- [ ] The `r2.dev` public URL is disabled after the custom domain works.

### Queue and operations

- [ ] `php artisan queue:monitor database:article-audio,database:default --max=100` completes without configuration errors.
- [ ] Supervisor reports the worker as `RUNNING`.
- [ ] A queued mail/notification is processed from `default`.
- [ ] A full article-audio job completes without timeout or duplicate execution.
- [ ] `php artisan queue:failed` is empty after acceptance tests.
- [ ] `php artisan schedule:list` matches the expected production schedule.
- [ ] `storage/logs/laravel.log` and `worker.log` contain no new exceptions.

### Performance and security

- [ ] Laravel config, routes, events, Filament components, and views are cached.
- [ ] Static Vite assets have immutable cache headers; HTML is not cached in a way that breaks auth or CSRF.
- [ ] R2 media has correct content types and an intentional cache policy.
- [ ] Cloudflare SSL is Full (strict); origin HTTPS remains valid.
- [ ] CloudPanel/Nginx restores the real client IP only from trusted Cloudflare proxy ranges; the origin is not trusting arbitrary `CF-Connecting-IP` headers.
- [ ] `.env`, `.git`, logs, backups, and private storage are not web-accessible.
- [ ] GitHub deploy keys and R2 tokens have least privilege.
- [ ] Database and R2 recovery procedures have been tested, not merely configured.

## 10. Rollback and recovery

### Application rollback

Use Deployer's previous release rollback, then restart workers:

```bash
vendor/bin/dep rollback production
php artisan queue:restart
```

An application rollback does not reverse a database migration. Every production migration must therefore be backward-compatible with the previous release whenever practical.

### Failed release before symlink switch

If migrations or build validation fail before activation, leave `current` untouched, inspect the failed release, and redeploy after fixing the cause. Do not manually patch files inside the active release.

### Database recovery

- Take an automated daily MySQL backup outside the site directory.
- Keep at least one copy outside the production server.
- Encrypt backups and test restoration on an isolated database.
- Take an on-demand backup before structural migrations.

### R2 recovery

- Enable R2 object versioning if it fits the account's recovery/cost policy.
- Use lifecycle rules intentionally; never expire active media.
- Keep bucket deletion and token-administration permissions separate from the application's read/write token.
- Document how an object or bucket is restored before production launch.

## 11. Implementation boundary

This document prepares the production architecture and runbook. The following implementation files still need to be created and reviewed before deployment automation is active:

- `deploy.php`
- `.github/workflows/deploy.yml`
- production health check uses the existing Laravel `/up` endpoint; add a deeper application check only if monitoring later requires database or storage verification
- committed S3 Flysystem adapter dependency
- configurable Article/Project Media Library disks, tests, and an existing-object migration plan
- server-side Supervisor configuration
- server-side cron entry

The production server, CloudPanel site, DNS, TLS, R2 bucket, GitHub Environment, secrets, and `.env` must be configured through their respective control planes; they are intentionally not stored in the repository.

## 12. Official references

- Laravel 13 deployment: <https://laravel.com/docs/13.x/deployment>
- Laravel 13 queues and Supervisor: <https://laravel.com/docs/13.x/queues#supervisor-configuration>
- Laravel 13 filesystem / S3-compatible storage: <https://laravel.com/docs/13.x/filesystem>
- Deployer Laravel recipe: <https://deployer.org/docs/8.x/recipe/laravel>
- Cloudflare R2 S3 API: <https://developers.cloudflare.com/r2/get-started/s3/>
- Cloudflare R2 public custom domains: <https://developers.cloudflare.com/r2/buckets/public-buckets/>
