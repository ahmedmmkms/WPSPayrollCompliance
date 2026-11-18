# WPS Payroll Compliance

An independent MVP that validates payroll data against Wage Protection System (WPS) rules, surfaces anomalies before a file reaches the bank, and tracks exceptions to resolution. It is bilingual, multi-tenant from day one, and runs on a free-tier stack.

## Product Brief

_Status: independent MVP with a live demo. Not yet used by real customers; the problem and metrics below are assumptions a pilot would test._

**Problem.** Companies in the UAE and Saudi Arabia must pay salaries through the Wage Protection System and submit bank-ready files. Payroll teams often check employee data by hand in spreadsheets, so errors surface only when a file is rejected and salaries are delayed.

**Who it's for.** Payroll managers and operations teams, including providers that run payroll for several companies.

**Key decisions and trade-offs**
- **Validate before submission.** Imports show a preview and flag exceptions, so errors get fixed before the bank sees the file.
- **Rules encoded once and reused across tenants,** so a regulation change is made in one place.
- **Multi-tenant from day one,** because payroll providers serve many companies. Trade-off: more complexity in the first release.
- **Exceptions as a tracked workspace** with owners, SLA counters and audit trails, not just an error list.
- **Free-tier hosting** keeps MVP cost near zero, at the price of limited throughput.

**Deliberately out of scope:** calculating salaries. The product validates payroll data and prepares files; it doesn't compute pay.

**What a pilot would measure:** first-time-right submission rate, exceptions per batch and time to resolve them, and time from import to a bank-ready file.

## What It Does
- **Compliance without chaos** – Encode Mudad and SIF rules once, reuse them for every tenant, and prove adherence with traceable audit data.
- **Tenant isolation by default** – Stancl Tenancy segregates payroll data and KPIs so each company's data stays inside its own tenant.
- **Bilingual UX + PWA** – Arabic and English experiences with RTL layouts, offline support, and install prompts in a single codebase.
- **Free-tier friendly** – Runs on Alwaysdata + Neon + Upstash with automated GitHub Actions deploys and zero containers to babysit.
- **Observable operations** – Filament dashboards, KPI APIs, and queue baselines keep payroll runs predictable during month-end spikes.

## Feature Highlights
- **Filament Admin Portal** – Role-aware widgets, Livewire forms, and SSO-ready guards for payroll managers.
- **Employee Import Pipeline** – Spreadsheet validation, preview, and queued ingestion with exception surfacing.
- **Exception Workspace** – Track, triage, and resolve payroll exceptions with SLA breach counters and audit trails.
- **Tenant KPIs** – `/admin/kpi/*` endpoints expose throughput and exception trends for custom dashboards.
- **Localization & PWA Shell** – Runtime locale switching, translated manifests, and service worker caching for resilience.
- **Blue/Green Deploys** – GitHub Action promotes atomic releases on Alwaysdata and prunes previous builds automatically.

## Architecture at a Glance
```mermaid
graph TD
    Browser
    Browser -->|HTTPS| CloudflareCDN[Cloudflare DNS / SSL]
    CloudflareCDN --> AlwaysdataApp[Alwaysdata Laravel App]
    AlwaysdataApp -->|Postgres TLS| Neon[(Neon Postgres)]
    AlwaysdataApp -->|Redis TLS| Upstash[(Upstash Redis)]
    GitHubActions[GitHub Actions] -->|Release sync| AlwaysdataApp
```

```mermaid
graph LR
    subgraph Tenant Space
        PayrollBatches -- validates --> ValidationEngine[Mudad Rule Engine]
        ValidationEngine -- emits --> Exceptions[Payroll Exceptions]
        Exceptions -- feeds --> KPIMetrics[Tenant KPI Metrics]
    end

    FilamentAdmin -->|Livewire| PayrollBatches
    KPIMetrics -->|JSON KPIs| FrontendCharts[Admin Dashboards]
```

## Tech Stack
- ⚙️ **Backend**: PHP 8.3, Laravel 12, Stancl Tenancy, Livewire 3, Filament 3, Laravel Prompts, Laravel MCP.
- 🗄️ **Databases**: 🐘 Neon (Postgres), Upstash Redis, database-backed cache store for rate limiting.
- 🎨 **Frontend**: ⚡ Vite, 🎨 Tailwind CSS 4, ⛰️ Alpine.js via Livewire components, 📱 PWA service worker.
- 🧪 **Tooling & QA**: Pest 3, PHPUnit 11, Laravel Pint, Lighthouse CI, GitHub Actions CI/CD.
- ☁️ **Hosting & Delivery**: Alwaysdata PHP runtime, Cloudflare DNS/SSL edge, Vercel (optional docs site).

## Screenshots
![Landing (English)](site_screenshots/Main.png)
![Landing (Arabic)](site_screenshots/Main_ar.png)
![Admin Dashboard](site_screenshots/dashboard.png)

## API Overview
| Method | Endpoint | Auth | Description |
| --- | --- | --- | --- |
| GET | `/health` | None | Simple uptime probe returning `{ "status": "ok" }`. |
| GET | `/admin/kpi/throughput` | Session (tenant) | Returns daily payroll batch throughput grouped by status. |
| GET | `/admin/kpi/exceptions` | Session (tenant) | Exposes opened vs resolved exceptions, status snapshot, and SLA breach count. |
| POST | `/locale/{locale}` | None | Switches user locale and stores it in the session. |
| POST | `/livewire/update` | Session | Livewire transport for Filament forms, including login and admin interactions. |

### KPI Response Sample
```json
{
  "labels": ["2025-09-21", "2025-09-22"],
  "datasets": [
    {
      "key": "completed",
      "label": "Completed",
      "data": [12, 18]
    },
    {
      "key": "processing",
      "label": "Processing",
      "data": [3, 1]
    }
  ],
  "generated_at": "2025-09-22T18:04:02+00:00"
}
```

## Getting Started
1. Clone the repository and copy `.env.example` to `.env`.
2. Install dependencies with `composer install` and `npm install`.
3. Generate an app key: `php artisan key:generate`.
4. Configure Neon + Upstash credentials along with the `OPS_ADMIN_*` seeded user.
5. Run `php artisan migrate` followed by `php artisan tenants:create --name="Acme" --domain="acme.test" --email="ops@acme.test"`.
6. Build assets (`npm run build`) or start hot reload (`npm run dev`).
7. Serve locally with `php artisan serve` and log in via `/admin/login`.

## Testing & Quality Gates
- Execute the full suite with `php artisan test`.
- Lint touched PHP files using `vendor/bin/pint --dirty`.
- For tenant-isolated checks, drive Livewire flows via `php artisan test tests/Feature/ --filter=Tenant`.
- Capture queue throughput baselines using `php artisan queue:baseline TENANT_ID --runs=1` and archive JSON outputs for performance audits.

## Deployment Notes
- GitHub Actions workflow `.github/workflows/deploy-alwaysdata.yml` performs blue/green releases into `~/www/wps-payroll/releases/<run-id>` on Alwaysdata.
- Shared storage lives under `~/www/wps-payroll/shared`; releases symlink `storage/` during promotion.
- Only the newest release is retained to respect free-tier disk quotas.
- Update the Alwaysdata site root to `~/www/wps-payroll/current/public` so Apache follows the active symlink.

## Author & Links
- Built by **Ahmed Mahmoud Moustafa**, technical product manager and systems engineer, as part of an independent product portfolio: [github.com/ahmedmmkms](https://github.com/ahmedmmkms).
- Connect on LinkedIn: [![Ahmed Mahmoud](https://img.shields.io/badge/LinkedIn-Ahmed%20Mahmoud-0A66C2?logo=linkedin&logoColor=white)](https://www.linkedin.com/in/ahmedmmkms/)

## License
This project is licensed under the **Apache License 2.0**. See the `LICENSE` file (or add one if missing) for the full text.
