# Production Runbook (Free Tier)

> Operating guide for WPS Payroll Compliance deployed entirely on free services. Update with every environment change.

## 1. Architecture Overview
- Topology diagram: see [docs/topology.md](topology.md).
- **Alwaysdata (Web Application)**: Native PHP host (1 vCPU burstable, 256 MB RAM) with git-based deploys. Provides HTTPS via Let's Encrypt and does not require containers.
- **PlanetScale (MySQL)**: Primary database. Free tier with 5 GB storage, 10M row reads/day. Production branch (`main`) plus deploy requests for schema changes.
- **Upstash Redis**: Queue and cache. Free tier 10K commands/day, 256 MB. Horizon powered job queues and cache tags share the instance.
- **Vercel (Optional)**: Marketing/API documentation site. Hobby plan 100 GB bandwidth.
- **Observability**: Application logs stored via default Laravel channels; rely on manual checks until monitoring stack is added.

## 2. Provisioning Steps
1. **Alwaysdata**
   - Create an Alwaysdata account and add a new **Web > Site** pointing to the `public/` directory.
   - Select PHP 8.3 runtime with Composer enabled. Configure the document root to `<project>/public`.
   - Enable git deployment: add the repository SSH public key as an **Authorized key** under **Remote access** and note the git URL `ssh://<account>@ssh.alwaysdata.com/<account>/<app>.git`.
   - Load environment variables from `.env.production` (see Section 5) via the Alwaysdata **Environment variables** panel.
   - Configure Let's Encrypt certificate and map the desired domain (default Alwaysdata subdomain works for staging).
2. **PlanetScale**
   - Create database `wps_payroll_prod`.
   - Generate passwordless connection URL; add it to the GitHub secret `PLANETSCALE_DB_URL` and document rotation details.
3. **Upstash Redis**
   - Create database in the closest region to Alwaysdata.
   - Copy REST and TLS credentials; map to secrets `UPSTASH_REDIS_REST_URL`, `UPSTASH_REDIS_REST_TOKEN`, `REDIS_URL`.
4. **Observability Placeholder**
   - Document where logs are written (Alwaysdata web logs and Laravel log files).
   - Capture manual steps for reviewing recent errors.
5. **Credential Tracking**
   - Track credential owners and rotation cadence in docs/accounts.md.
   - Update `docs/accounts.md` owner table.

## 3. Deployment Workflow
1. **CI Build** (`ci.yml`)
   - Runs tests and static analysis for PHP and containers (see Section 7 for weekly checks).
2. **Blue/Green Deploy** (`deploy-alwaysdata.yml`)
   - Executes on push to `main`/`master`.
   - Validates Alwaysdata API/SSH credentials, prepares release directories under `~/www/wps-payroll/releases/<release-id>` and stages shared storage inside `~/www/wps-payroll/shared`.
   - `rsync` uploads the application (Composer dependencies included) into the release directory while keeping `storage/` symlinked to `shared/storage`.
   - Remote post-deploy hook runs database migrations, cache priming, and creates a `previous` pointer before atomically promoting `current → <release-id>`.
   - Old releases beyond the most recent three are purged to control disk usage.
3. **Post-Deploy Verification**
   - Hit `/health` manually or rely on `Uptime Ping` workflow.
   - If issues arise, repoint `~/www/wps-payroll/current` back to `~/www/wps-payroll/previous` and investigate before re-running the pipeline.

## 4. Incident Response
- **Alwaysdata Down/Sleeping**: Check Alwaysdata status page, inspect web server logs, and restart the site from the dashboard.
- **PlanetScale Limits**: Review read metrics, archive batches, or schedule maintenance window; upgrade tier if sustained load.
- **Redis Commands Exhausted**: Reduce queue concurrency, batch jobs off-peak, or trim cache usage.
- **Credential Issues**: Retrieve from the documented secret source; rotate via backup owner if compromised.
- **Rollback**: Push previous commit SHA to the Alwaysdata git remote or re-run deploy workflow pointing to a prior artifact.

## 5. Environment Variables (Alwaysdata)

GitHub Secrets:
- `ALWAYSDATA_ACCOUNT`: Alwaysdata account name used for API calls and hostnames.
- `ALWAYSDATA_API_TOKEN`: Personal API token generated under **Account → Users & API**.
- `ALWAYSDATA_SITE_ID`: Numeric site identifier for API-triggered deploys or restarts.
- `ALWAYSDATA_DEPLOY_KEY`: Private key granting push/SSH access to the Alwaysdata account.
- `ALWAYSDATA_SSH_HOST`: Typically `ssh.alwaysdata.com`.
- `ALWAYSDATA_SSH_USER`: Alwaysdata account username.
- `PLANETSCALE_DB_URL`: PlanetScale passwordless connection string.
- `UPSTASH_REDIS_REST_URL`, `UPSTASH_REDIS_REST_TOKEN`: Optional REST credentials for queue management.

Secrets for PlanetScale and Upstash live in GitHub repository settings. Update them manually (Settings → Secrets and variables → Actions) or with the GitHub CLI when rotations occur.

| Key | Source | Notes |
| --- | --- | --- |
| `APP_ENV` | static | `production` |
| `APP_KEY` | Vault | Generate with `php artisan key:generate --show` locally |
| `APP_URL` | Alwaysdata site URL | Example `https://wpspayrollcompliance.alwaysdata.net` |
| `DB_CONNECTION` | static | `mysql` |
| `DATABASE_URL` | PlanetScale | Preferred full connection string |
| `REDIS_URL` | Upstash | TLS URI |
| `REDIS_REST_URL` | Upstash | Optional for REST ingestion |
| `QUEUE_CONNECTION` | static | `redis` |
| `FILESYSTEM_DISK` | config | Stream exports directly; leave unset or use `local` for temporary files |

### Secret Rotation Flow
1. Generate new PlanetScale passwordless URL and/or Upstash Redis tokens in their dashboards; capture them in the shared vault.
2. Update GitHub repository secrets (`PLANETSCALE_DB_URL`, `REDIS_URL`, `UPSTASH_REDIS_REST_URL`, `UPSTASH_REDIS_REST_TOKEN`) via the UI or `gh secret set`.
3. Trigger the Alwaysdata blue/green deploy (push to `main` or rerun the workflow) and verify `/health` plus Horizon queues.
4. Once verified, revoke the previous credentials and log the rotation date in `docs/accounts.md`.

## 6. Compliance Notes
- Export jobs stream results directly to the requester; regenerate files when a historical copy is needed.
- PlanetScale provides encryption at rest; confirm retention policies match UAE/KSA expectations.
- Audit trails for payroll batches prune automatically via `php artisan audit:prune` (scheduled daily at 02:00 UTC). Override retention with `AUDIT_TRAIL_MAX_EVENTS` / `AUDIT_TRAIL_MAX_AGE_DAYS` as needed and capture dry-run output for DPIA evidence.
- DPIA update log maintained in [docs/dpia-checklist.md](dpia-checklist.md); review before go-live sign-off.
- Plan Laravel audit logging strategy after selecting a hosted monitoring service.

## 7. Weekly Checks
- Review Alwaysdata, PlanetScale, and Upstash dashboards for quota proximity.
- Verify `Uptime Ping` workflow succeeded during the week (no failed runs).
- Capture queue throughput monthly with `php artisan queue:baseline TENANT_ID --runs=1` (see [docs/performance-baseline.md](performance-baseline.md)) and archive the JSON output in release records.
- Update `docs/accounts.md` if ownership or secrets change.

## 8. Localization & PWA Sign-off
- Follow `docs/localization-pwa.md` for bilingual acceptance criteria; QA documents sign-off each release.
- Archive Arabic copy approvals in docs/quotas.md and sync translation keys when content evolves.
- Service worker (`/service-worker.js`) precaches the landing shell and `/offline`; confirm manifest headers served at `/manifest.webmanifest` after deploy.

## 9. Open Items
- Use `php artisan tenants:create` to provision new customer tenants; ensure tenant domains resolve locally before QA.
- Alwaysdata site reachable at https://wpspayrollcompliance.alwaysdata.net.
- GitHub secrets maintained manually; ensure rotation dates and owners stay in sync across docs/accounts.md and the shared vault.
- Laravel session auth: store `OPS_ADMIN_*` credentials securely in GitHub secrets and the central vault.
- Bilingual placeholder (`public/index.php`) returns 200; full Laravel app rollout tracked for Sprint 1.
- Document PlanetScale/Upstash provisioning details and update docs/accounts.md after each rotation.
- Decide on additional uptime monitoring or alerting beyond GitHub Actions (e.g., log aggregation later).
- Evaluate future IaC/script automation (currently deferred).

## 10. Native PHP Validation
- Use `php --version` locally and on Alwaysdata to confirm PHP 8.3 runtime parity.
- Run `php artisan config:cache` and `php artisan route:cache` after deployment for warm cache checks.
- No containers are in scope; all smoke testing relies on the native PHP runtime.
- Secrets managed through GitHub repository settings; document rotations in docs/accounts.md.
