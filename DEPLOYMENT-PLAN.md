# Ibrahim Hasan Website — Controlled Release Runbook

This is the repository's only operational source of truth. It defines the path from a reviewed commit to a reversible, observable release. It does not authorize production changes by itself.

## 1. Change control and non-negotiable safety rules

- Do not commit, push, deploy, rotate credentials, alter DNS, create infrastructure, or change a production secret without separate explicit authorization.
- Deploy a reviewed immutable Git SHA and the exact frontend artifact built for that SHA. Never deploy an implicit branch head.
- Production deployment must be manual and protected. A source push, pull request, or merge must never deploy production automatically.
- Keep staging and production completely separate: hostnames, system users, release paths, databases, Redis prefixes, application keys, Passport key pairs, deploy keys, mail delivery, AI/audio quotas, R2 buckets, and analytics settings.
- Never put credentials, connection strings with credentials, private keys, bearer values, recovery codes, or token-shaped examples in this document, source control, CI logs, artifacts, tickets, or screenshots.
- Preserve the existing dirty Article and Athar workstreams while release work proceeds. Do not reset, clean, stash, or overwrite user-owned changes.
- Never seed production during a normal deployment. Do not move serialized jobs between queue backends. Do not blind-rollback production migrations or purge queues during a code rollback.

## 2. Verified repository baseline

| Area | Repository truth |
|---|---|
| Framework | Laravel 13 on PHP 8.4 |
| Queue runtime | Redis-backed Laravel Horizon 5, with a `super_admin`-gated dashboard |
| Queue topology | Dedicated `default` and `article-audio` Horizon supervisors |
| Audio timeout chain | Job 1560s < Horizon 1620s < Redis visibility 1800s |
| Scheduler | `horizon:snapshot` every five minutes; production-only Passport, editorial audit, Athar expiry, and conditional privacy-purge jobs |
| Storage | S3-compatible adapter is installed; media disk remains configurable |
| Deployment tooling | Deployer 8 configuration and a GitHub Actions production workflow exist |
| Retained releases | Five release directories |

The current GitHub workflow validates pushes to `production` and permits releases only through a manual exact-SHA dispatch. A production dispatch also requires the matching successful staging artifact and the configured protected environment approval. The Deployer recipe fails closed when either shared Passport key is missing or has unsafe permissions; it does not generate replacement keys during deployment. The external staging, environment-protection, secret, and host prerequisites in this runbook must still be completed before any release is authorized.

## 3. Environment topology

### Production

- Public application: `https://ibrahimhasan.net`
- Public media: `https://media.ibrahimhasan.net`
- Dedicated CloudPanel site user and release path
- Dedicated MySQL database and least-privilege database user
- Redis with a production-specific prefix; never Redis Cluster for Horizon
- Cloudflare Full (strict), after direct origin HTTPS verification

### Protected staging

- Application: `https://staging.ibrahimhasan.net`
- Public media: `https://media-staging.ibrahimhasan.net`
- Cloudflare Access required before application access
- Dedicated CloudPanel site user, release path, database, database user, Redis prefix, application key, Passport key pair, SSH/deploy key, GitHub environment, mail sandbox, AI/audio quota credentials, and R2 bucket
- `APP_DEBUG=false`, no analytics, and a site-wide noindex response policy
- No copied production PII. Use synthetic or irreversibly anonymized fixture data only.

Every included HTTPS subdomain must be ready before enabling HSTS. Do not enable COEP, COOP, or CORP globally without compatibility evidence.

## 4. Secret and credential controls

### Secret sources and permissions

- Store production and staging secrets only in their respective approved secret stores or server-side protected environment files.
- Ensure environment files are readable only by the corresponding site user.
- Use separate read-only repository deploy keys for server checkout and separate CI-to-server deployment keys. Pin known-host fingerprints; do not perform runtime host-key scanning.
- Scope database users to their own database. Scope R2 tokens to the required bucket and object operations only. Keep bucket administration and deletion authority separate from the application token.
- Keep Passport private and public keys as a matched, pre-provisioned pair. The release must fail if either key is missing or has unsafe permissions. Never invoke `passport:keys --force` during deployment, and never replace a surviving key merely because its counterpart is absent.

### Credential exposure response

If a credential-like value is found in a tracked file, old release, log, export, backup, source map, GitHub Action log/artifact, or server environment:

1. Record only redacted evidence in the restricted incident record.
2. Determine whether it was ever valid and where it was used.
3. If validity cannot be disproved, rotate it with a least-privilege replacement before release.
4. Update the affected server secret, rebuild configuration caches, restart Horizon, and verify web, CLI, scheduler, and queue connectivity before revoking the old value.
5. Scan the same surfaces for related exposure. Do not rewrite shared Git history unless legal or security owners explicitly require it.

This investigation and any rotation require separate production authorization.

### Administrator MFA break-glass

Break-glass is an exceptional, server-authorized recovery procedure, not a routine sign-in alternative. Before it is used, an authorized operator must verify the administrator's identity out of band and record the approver, time, reason, affected account, and recovery outcome in the restricted audit record.

The controlled recovery procedure must reset the account password through an authorized administrative path, clear both stored application-authentication fields, invalidate active sessions and recovery codes, and require password rotation plus fresh TOTP enrollment before normal access is restored. Keep the implementation-specific server steps and all secret material out of this runbook. Every execution requires post-incident review.

## 5. Queue, Horizon, and scheduler operation

### Horizon supervisors

Horizon must run against a standalone Redis connection. The application configuration intentionally isolates workloads:

| Supervisor | Queue | Initial processes | Memory | Horizon timeout | Tries | Long-wait alert |
|---|---|---:|---:|---:|---:|---:|
| `supervisor-default` | `default` | 1 | 256 MB | 300s | 3 | 60s |
| `supervisor-article-audio` | `article-audio` | 1 | 512 MB | 1620s | 1 | 300s |

Both supervisors use fixed balancing so audio work cannot consume the default queue's worker. Scale only after evidence of CPU, memory, API limits, queue waits, and duplicate-generation behavior supports a change.

Horizon retains failed and recently failed job records for 10,080 minutes (seven days). Keep the configured metrics snapshot schedule running so the dashboard has current queue history.

The timeout ordering is mandatory:

```text
article-audio job timeout: 1560 seconds
Horizon audio supervisor timeout: 1620 seconds
Redis queue retry_after: 1800 seconds
system service stopwaitsecs: 1860 seconds
```

The Horizon timeout must remain greater than the job timeout and below `retry_after`; the host process must outlive the longest worker shutdown. The database queue's fallback visibility default is also 1800 seconds, but production jobs must dispatch to Redis.

### Host process

Run `php artisan horizon` under the host process manager, not `queue:work`. Configure automatic restart, group termination, a production-safe working directory, and `stopwaitsecs=1860`. Restart workers through `horizon:terminate` after activation so they reload the new release. Restrict `/horizon` to the existing `super_admin` gate and a protected administrative path.

If legacy database queue rows exist during the Redis migration:

1. Switch new dispatches to Redis.
2. Start and verify Horizon.
3. Tell legacy workers to stop when empty.
4. Wait for the database jobs table to reach zero pending rows.
5. Stop legacy workers.

Never deserialize or manually transfer jobs between backends.

### Scheduler

Install one system scheduler trigger per environment. The scheduler currently owns:

- Horizon metrics snapshots every five minutes.
- Production Passport cleanup.
- Production editorial API audit-log cleanup.
- Production Athar invitation expiry.
- Conditional production privacy-retention purge.

The release requires a scheduler heartbeat, alerting when it is older than two minutes, and `schedule:list` evidence after each release. After each release activation or rollback, Deployer runs `app:record-scheduler-heartbeat --no-interaction` before `app:release-check --no-interaction`; this confirms the active release can write the heartbeat but does not replace the once-per-minute scheduler task. Horizon metrics require the existing snapshot schedule; do not consider the dashboard healthy merely because the Horizon process is running.

## 6. Storage, media, and data separation

- Keep framework cache, logs, sessions, and private application storage on the shared protected release storage as configured for the site.
- Use a dedicated production R2 bucket and a separate staging bucket. Serve approved public media only through the appropriate custom media domain.
- Do not make private storage, backups, logs, environment files, or source maps public.
- Before changing a media disk or R2 delivery configuration, verify upload, conversion, read, range requests for audio, deletion, and durable URLs using a disposable test object; then remove that test object.
- A media migration needs an inventory, read-back audit, retry plan, and rollback plan before switching public delivery. Do not alter vendor files on a server to test storage.

## 7. Readiness, monitoring, and alert hygiene

`/up` remains the liveness endpoint. Before a production release, implement and prove the following deeper controls:

- `app:release-check`, with redacted results for database, Redis, scheduler heartbeat, required storage, pending migrations, Horizon, critical configuration, and deployed revision.
- Protected `/health/ready`, rate limited and checked with a constant-time secret-header comparison. It returns only `204` or `503`, with no component details, paths, exceptions, credentials, or versions.
- A deployed-revision file and correlation identifier.
- Alert deduplication and recovery notifications.
- A daily local and N8N operational report with redacted payloads.

Provision a non-sensitive `.healthcheck` marker on each required local and media/audio storage disk before the first release. The release check reads those markers only; it never creates, replaces, or deletes them.

Monitor every five minutes:

- liveness and readiness;
- Arabic and English Home responses;
- default queue wait over 60 seconds;
- audio queue wait over 300 seconds;
- scheduler heartbeat older than two minutes;
- newest successful backup older than 26 hours;
- disk use above 80 percent; and
- TLS certificate expiry under 30 days.

Alerts and telemetry must never include inquiry content, comments, names, emails, IP addresses, raw URLs, bearer values, stack traces, request bodies, or other PII.

## 8. CI and controlled release workflow

### CI on every push and pull request

Run in an isolated environment with PHP 8.4, MySQL 8, Redis, and Node 22:

1. Validate Composer metadata and perform deterministic dependency installation.
2. Run the Composer security audit and npm security audit.
3. Run `npm ci` and the production frontend build.
4. Run the full PHPUnit suite.
5. Run `composer lint:check`.
6. Run fresh MySQL migrations.
7. Assert Horizon configuration, queue timeout ordering, scheduler registration, content publication checks, SEO/security checks, and forbidden artifact/cache checks.
8. Run repository-owned tracked-secret pattern checks.

CI and pull requests must not receive production secrets.

### Manual exact-SHA release

The replacement GitHub workflow must require an operator to select an exact validated Git SHA and target environment:

1. Verify CI for the chosen SHA.
2. Build one immutable frontend artifact for that SHA.
3. Deploy that SHA and artifact to protected staging.
4. Run staging migrations, release checks, protected readiness, queue/scheduler smoke tests, and the approved browser QA matrix.
5. Require protected production-environment approval.
6. Deploy the exact same SHA and artifact to production.
7. Run production release checks and smoke tests.
8. Retain release evidence for 90 days.

Use Deployer's exact revision/target support. The deploy job needs only the narrowest possible repository permission, separate staging/production keys, pinned host fingerprints, one concurrency group, no production seeding, no key generation, five retained releases, and a manual protected rollback action.

## 9. Backups, restore drills, and privacy alignment

Before the first release governed by this runbook, provision and prove:

- nightly encrypted full database backups;
- hourly off-host binary logs retained for seven days;
- fourteen daily and eight weekly database backups;
- a pre-migration backup retained for 30 days;
- nightly media and private-storage backups under separate credentials;
- application-key and Passport-key escrow in the approved secret manager; and
- quarterly isolated restore drills.

The target recovery point objective is one hour and the target recovery time objective is four hours. Backup retention and deletion must align with the published privacy policy, including backup-deletion delay. A backup that has not been restored in an isolated environment is not a verified backup.

## 10. Staging and production release gates

Before production approval, retain evidence that all of the following passed on the exact candidate:

- configuration cache rebuild and route cache behavior;
- Laravel migrations with no pending migration after deployment;
- both Horizon supervisors running and processing their intended queues;
- `horizon:snapshot` present in `schedule:list` and metrics arriving;
- default queue notification and an audio job completing without timeout or duplicate execution;
- protected readiness and public Arabic/English Home checks;
- media upload/conversion/playback/deletion against the environment-specific media domain;
- consent-dependent analytics behavior;
- backup restore drill and rollback rehearsal;
- Arabic and English core journeys, accessibility, privacy, CSP, security-header, and browser QA gates from the implementation plan.

Staging must be production-shaped, but it must not use production credentials or PII. The production candidate is rejected if staging cannot prove the same SHA and artifact.

## 11. Rollback and incident response

Use release-symlink rollback to a known-good release only after checking the migration and queue compatibility. A code rollback does not reverse schema changes.

- Prefer a forward fix after a schema migration has run.
- Do not run blind production migration rollback.
- Do not purge queues as a rollback shortcut.
- Drain or isolate incompatible jobs before changing workers.
- Roll back immediately for authentication bypass, secret exposure, PII leakage, data loss, persistent 5xx loops, blank Arabic or English core journeys, pre-consent analytics, or inaccessible primary navigation or Contact.

For a failed release before symlink activation, leave the active release untouched, preserve redacted diagnostics, fix the candidate, and redeploy through the same controlled process. Do not patch files inside an active release.

## 12. Required external-authority actions

The repository can define the contracts above but cannot perform these actions without the appropriate access and explicit approval:

- Credential-exposure investigation and any required rotation.
- CloudPanel users, site roots, PHP extensions, process-manager entries, and scheduler triggers.
- Cloudflare DNS, TLS, Access, R2 buckets, custom domains, and HSTS.
- GitHub branch protection, environments, approvals, deploy keys, and workflow changes.
- Staging and production databases, Redis instances/prefixes, mail sandbox, monitoring, secret-manager, and backup systems.
- Passport key provisioning/rotation, MFA break-glass execution, and production deployment/rollback.

No one may treat this document as approval to perform those actions.

## 13. Official references

- [Laravel Horizon](https://laravel.com/docs/13.x/horizon)
- [Laravel queues](https://laravel.com/docs/13.x/queues)
- [Laravel deployment](https://laravel.com/docs/13.x/deployment)
- [Deployer Laravel recipe](https://deployer.org/docs/8.x/recipe/laravel)
- [Deployer update code](https://deployer.org/docs/8.x/recipe/deploy/update_code)
- [Cloudflare R2 S3 API](https://developers.cloudflare.com/r2/get-started/s3/)
