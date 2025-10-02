# Deployment Account Registry

> Maintain current credential owners, quotas, and GitHub secret mappings. Update after every account change.

## Usage Checklist
- [ ] Account created and verified
- [ ] GitHub secrets populated
- [ ] Limits reviewed against Sprint objectives
- [ ] Owner & rotation dates confirmed

## Account Matrix

| Service | Purpose | URL | Plan / Limits | GitHub Secrets | Notes |
| --- | --- | --- | --- | --- | --- |
| Alwaysdata | Laravel app hosting (staging/production) | https://alwaysdata.com | Free tier: 1 shared CPU, 256 MB RAM, 100 MB disk | `ALWAYSDATA_ACCOUNT`, `ALWAYSDATA_API_TOKEN`, `ALWAYSDATA_SITE_ID`, `ALWAYSDATA_DEPLOY_KEY`, `ALWAYSDATA_SSH_HOST`, `ALWAYSDATA_SSH_USER` | Rotate API token every 180 days (next 2025-09-25). |
| GitHub Actions | CI/CD pipelines | https://github.com/features/actions | Org free tier: 2K minutes/month | PAT stored in org secrets (rotate every 90 days). | Next rotation 2025-09-25. |
| PlanetScale | MySQL database | https://planetscale.com | Free tier: 5 GB storage, 10M row reads/day | `PLANETSCALE_DB_URL` | Secrets maintained manually in GitHub; log rotations and update shared vault immediately. |
| Upstash Redis | Queues & cache | https://upstash.com | Free tier: 10K commands/day, 256 MB | `REDIS_URL`, `UPSTASH_REDIS_REST_URL`, `UPSTASH_REDIS_REST_TOKEN` | Secrets maintained manually in GitHub; rotate REST token every 90 days (next 2025-09-25). |
| Cloudflare DNS | DNS & TLS proxy | https://cloudflare.com | Free plan | `CLOUDFLARE_API_TOKEN` | Token rotation every 180 days (next 2025-09-25). |
| Vercel | Marketing/docs hosting | https://vercel.com | Hobby: 100 GB bandwidth, 100 deployments/day | `VERCEL_TOKEN`, `VERCEL_PROJECT_ID` (if used) | Confirm project token ownership quarterly. |

## Owner Registry

| Service | Primary Owner | Backup Owner | Rotation Interval | Last Updated |
| --- | --- | --- | --- | --- |
| GitHub Actions | AMM | AMM | 90 days (PAT) | 2025-09-25 |
| Alwaysdata | AMM | AMM | 180 days | 2025-09-25 |
| PlanetScale | AMM | AMM | 180 days | 2025-09-25 |
| Upstash Redis | AMM | AMM | 90 days | 2025-09-25 |
| Cloudflare DNS | AMM | AMM | 180 days | 2025-09-25 |
| Vercel | AMM | AMM | 90 days | 2025-09-25 |

## Action Items
- Sign up for each service using the engineering shared email alias.
- Capture API keys/tokens in the agreed secure storage with expiration reminders.
- Populate `Repository secrets` in GitHub after tokens exist.
- Update this registry during sprint review or when owners change.
