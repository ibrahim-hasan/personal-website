# Ibrahim Hasan Website — Simple Production CI/CD

This is a personal website. The deployment path is intentionally simple:

```text
push to production → CI checks → automatic production deployment
```

There is no staging environment, release approval, artifact handoff, manual release form, or GitHub Actions rollback job.

## Daily workflow

1. Make and test the change locally.
2. Commit it and push it to `production`.
3. GitHub runs CI for that exact commit.
4. If CI passes, GitHub deploys that exact commit to production through Deployer.

`main` may mirror `production`, but only pushes to `production` deploy the site.

## CI checks

Every push to `production` runs:

- dependency installation;
- frontend production build;
- fresh MySQL migrations;
- public-content lint;
- the PHPUnit suite; and
- PHP formatting checks.

The deploy job runs only after these checks pass. It builds the frontend again and deploys the pushed Git SHA directly; no artifact is stored or selected manually.

## One-time GitHub setup

Keep a GitHub Actions environment named `production` only to hold the existing deployment secrets and variables. Do not require a reviewer or an approval rule for it.

Secrets:

- `DEPLOY_HOST`
- `DEPLOY_USER`
- `DEPLOY_SSH_PRIVATE_KEY`
- `DEPLOY_KNOWN_HOSTS`
- `DEPLOY_PORT` only when SSH does not use port 22
- `GOOGLE_REPORTING_SERVICE_ACCOUNT_JSON` — the read-only Google reporting service-account JSON
- `WEBSITE_METRICS_CLIENT_SECRET` — the dedicated Website Performance Reporter Passport client secret

Variables:

- `DEPLOY_PATH`
- `DEPLOY_HEALTH_URL`
- `WEBSITE_METRICS_API_CLIENT_ID` — the dedicated Website Performance Reporter Passport client ID
- `WEBSITE_PERFORMANCE_WEBSITE_URL` — the canonical public HTTPS origin used for reporting

Do not put secret values in this repository, Action logs, tickets, or screenshots.

## Production server requirements

The production server needs only:

- the existing shared `.env` file;
- the existing shared Passport key pair at `storage/oauth-private.key` and `storage/oauth-public.key`; Deployer secures both to mode `0600` before use;
- the configured public health endpoint;
- the existing Horizon process; and
- an active cron service and `crontab` command available to the deployment user.

Deployer maintains one marked `ibrahim-website-scheduler` entry in the deployment user's crontab. It runs every minute from the `current` release symlink with the deployment server's resolved PHP binary:

```text
* * * * * cd <current release> && <php> artisan schedule:run --no-interaction
```

The marked section is updated idempotently, preserves unrelated crontab entries, follows releases and rollbacks through `current`, and is verified before the public health check. Do not add a second manual scheduler cron entry or `schedule:work` process for this application.

Deployer keeps the latest five releases, applies the pushed revision, reloads Horizon, installs and verifies the scheduler runner, and checks the public health endpoint. The private readiness endpoint remains available for manual operational diagnosis, but is not a deployment prerequisite.

## If a release is bad

Revert the bad commit and push the revert to `production`:

```bash
git revert <bad-commit>
git push origin production
```

CI then deploys the revert automatically. Do not patch files inside a server release or run a blind database rollback.

## After a deployment

Check the public site, especially Arabic and English Home pages. If a deployment job fails, use the redacted GitHub Actions log to fix and push a new commit.
