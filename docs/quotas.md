# Free Tier Quota & Uptime Guide

> Track limits, automate alerts, and plan mitigations for production running on free services.

## Quota Dashboard

| Service | Limit | Current Monitoring Plan | Upgrade Trigger |
| --- | --- | --- | --- |
| Alwaysdata | 256 MB RAM, 1 vCPU burstable, 100 MB disk | Weekly manual check in Alwaysdata dashboard; GitHub Action cron ping to keep health endpoint warm | Sustained high CPU/mem alerts or need dedicated resources |
| PlanetScale | 5 GB storage, 10M row reads/day | Enable PlanetScale Slack/email alerts; review query stats weekly | Approaching 4 GB storage or sustained >8M reads/day |
| Upstash Redis | 10K commands/day, 256 MB | Daily command count email; Horizon dashboard watch | >8K commands/day or backlog builds |
| Vercel | 100 GB bandwidth/mo | Vercel analytics monthly | >80 GB bandwidth |

## Monitoring Automation
- **GitHub Actions Cron**: Add scheduled workflow hitting `/health` endpoint every 10 minutes to keep the Alwaysdata PHP worker warm and capture availability.
- **Status Alerts**:
  - PlanetScale: enable query alert for read/write spikes.
  - Upstash: set command threshold notifications.
- **Manual Reviews**: Document in runbook (weekly check). Assign owner to review Mondays and update `docs/accounts.md` owner registry.

## Mitigation Playbook
- **Queue Throttling**: Lower queue worker count via Alwaysdata environment variable, re-deploy.
- **Batch Windows**: Schedule heavy exports off-peak (cron job from GitHub runner).
- **Data Regeneration**: Re-run export jobs if a file is requested after the original download window.
- **Graceful Degradation**: If quotas hit mid-cycle, disable non-essential features (e.g., dashboards) until reset.

## Incident Log Template
```
Date:
Service:
Limit Reached:
Impact:
Mitigation Applied:
Follow-up Action:
```
Store entries under `docs/incidents/` for audit trail.
