# Project Plan - WPS Payroll Compliance & SIF Generator

## Goal
Deliver a compliant, multi-tenant payroll tooling that ingests employee payroll data, validates against UAE WPS and KSA Mudad regulations, and exports bank-ready SIF packages with full auditability within a 4-6 week MVP window.

## Immediate DevOps Requirement
- Stand up GitHub Actions CI/CD pipelines on day zero covering build, test, security scans, and deployment promotion gates for every branch. This is the first execution step for DevOps, prerequisite to any application work.

## Technology Stack
- **Backend:** PHP 8.3, Laravel 11, Filament 3 (admin UX), Laravel Tenancy/Stancl for tenant scoping, MySQL 8, Redis 7, Laravel Horizon, Pest/PHPUnit, PHPStan/Psalm, OpenAPI-powered controllers, Guzzle for external calls.
- **Admin Experience:** Filament resource pages, Livewire v3, Tailwind CSS, Alpine.js, Chart.js for KPIs, Filament Shield for RBAC policy scaffolding.
- **Localization & PWA:** Laravel Localization (Spatie package) for English/Arabic content, RTL-friendly Tailwind config, dynamic locale switcher, Laravel PWA (or Workbox/Vite plugin) for manifest, service workers, offline fallback, install prompts.
- **Data & Storage:** Encrypted MySQL schemas (per-tenant scoping) with on-demand export streaming (no persistent object store in MVP), Laravel Scout (optional) for advanced search.
- **Async & Integrations:** Horizon-managed queues on Redis for validation/export jobs, REST adapters for bank and Mudad endpoints, Laravel Events feeding shared audit/event bus.
- **DevOps & QA Tooling:** GitHub Actions CI/CD, Prometheus + Loki for observability, Trivy/Snyk for security scans, Lighthouse CI for PWA/RTL regressions.

## Free Tier Deployment & Services
- **Vercel (Hobby):** Host public marketing site, API documentation (Next.js) and status page; 100 GB bandwidth per month and 12 serverless function executions per minute limit - backend API remains on PHP runtime elsewhere.
- **Alwaysdata (Free Web Hosting):** Run the Laravel app on native PHP 8.3 without containers. One shared CPU with 256 MB RAM and 100 MB storage; ideal for staging and MVP traffic.
- **PlanetScale (Free):** Serverless MySQL with branch-based workflows; supports 5 GB storage and 10 million row reads per day - ideal for staging/UAT, migrate to paid or managed MySQL for production.
- **Upstash Redis (Free):** Serves queue workloads with 10,000 commands per day - suitable for dev/staging; upgrade for production throughput.
- **GitHub Actions (Free for org up to 2K minutes per month):** Automate tests, linting, container builds, deploy pipelines.

## Scope and Deliverables (MVP)
- Tenant onboarding with RBAC via shared Keycloak and Laravel tenancy scaffolding.
- Employee and payroll batch importers (CSV/XLSX) with schema validation and preview before commit.
- Configurable validation engine covering UAE WPS SIF rules and initial KSA Mudad checks with versioned rule sets.
- SIF generation service supporting multiple bank profile templates and queue-based export jobs (CSV/PDF summaries).
- Exception management workspace with assignment, status tracking, and notification hooks.
- Immutable audit logging, batch-level dashboards, and shared reporting widgets.
- Bilingual English/Arabic experience with full RTL support (layouts, typography, components, exports).
- Progressive Web App capability with responsive design across desktop, tablet, and mobile, including offline notice and install prompts.

## Sprint Breakdown (6-week MVP)
### Sprint 0 - Initial Deployment & Platform Setup (Week 0)
- **Objectives:** Establish baseline environments, deployment guidelines, and compliance-ready foundations.
- **Key Tasks:**
  - Execute GitHub Actions CI/CD pipeline setup (build/test/security/deploy) as first DevOps action; enforce branch protections.
  - Register and secure free-tier accounts (Alwaysdata, PlanetScale, Upstash, Vercel) and document quotas.
  - Define environment topology (Dev/Staging/Prod), networking, and secret management standards; publish deployment runbook v0.
  - Finalize Alwaysdata deployment scripting and validate a production build via GitHub Actions.
  - Document hosting/database/Redis provisioning steps and secret management runbooks.
  - Confirm native PHP local environment matches production extensions and runtime settings.
  - Draft localization/PWA acceptance criteria and gather bilingual content sources.
- **Exit Criteria:** Deployment playbook approved, pipelines green, baseline environments reachable, localization/PWA requirements captured.

### Sprint 1 - Tenant, Imports & Localization Foundations (Weeks 1-2)
- **Objectives:** Deliver tenant-aware core domain, import flows, and bilingual/RTL scaffolding.
- **Key Tasks:**
  - Implement Company/Employee/PayrollBatch domain models with tenancy middleware and encrypted storage.
  - Integrate Keycloak (OIDC) and Filament Shield policies; seed RBAC roles with locale awareness.
  - Build CSV/XLSX ingestion pipeline with schema validation, preview, and rollback.
  - Establish audit logging (event bus emitters) and baseline metrics probes.
  - Implement localization framework (language files, runtime locale switch, RTL Tailwind config) and responsive design tokens.
  - Stand up initial PWA manifest, service worker shell, and responsive layout smoke tests.
  - QA: write Pest feature tests for tenant isolation, importer validation, bilingual UI coverage.
  - DevOps: configure Alwaysdata staging deploy, bind to PlanetScale and Upstash dev resources.
- **Exit Criteria:** Multi-tenant bilingual login, data import wizard, automated tests passing in CI, PWA install prompt and RTL layout validated on staging.

### Sprint 2 - Validation Engine & SIF Generation (Weeks 3-4)
- **Objectives:** Codify WPS/Mudad rules, deliver bank-compliant exports, and deepen PWA capabilities.
- **Key Tasks:**
  - Create rule definition DSL (JSON/YAML) and execution service with Horizon workers.
  - Implement UAE WPS validators and deliver a KSA Mudad adapter with sandbox endpoints and template selection.
  - Develop SIF template library with versioned bank profiles (including Arabic labels where required) and export scheduling.
  - Stream generated files directly to users, exposing download/audit endpoints with locale-aware metadata.
  - Enhance service worker for offline queues, background sync, push notification hooks, and Horizon metrics surfacing.
  - QA: expand regression suite for validation outcomes, SIF golden files, and Lighthouse CI thresholds.
- **Exit Criteria:** Successful bilingual batch validation plus SIF export in staging, bank profiles versioned, queue metrics visible, PWA scores meeting targets.

### Sprint 3 - Exceptions, Reporting & Launch Readiness (Weeks 5-6)
- **Objectives:** Complete exception workflows, reporting, and production readiness with localization/PWA polish.
- **Key Tasks:**
  - Build exception center UI with assignment, SLA timers, activity logs, and bilingual notifications.
  - Implement KPI dashboards (Chart.js) with locale-aware number/date formatting and RTL charts.
  - Harden audit log retention and privacy review (DPIA, encryption validation) including data residency considerations for multilingual exports.
  - Performance tuning: load-test importer and queue throughput, adjust worker scaling, measure Lighthouse performance/SEO/PWA audits for desktop/mobile.
  - DevOps: finalize IaC, blue/green playbooks, secret rotation procedures.
  - QA/PM: coordinate UAT across English/Arabic audiences, capture feedback, prep training collateral, support runbooks, and handover.
- **Task Breakdown (commit-sized):**
  - [x] `S3-EX-01` Build Filament exception workspace layout (filters, status chips, RTL-ready table styling).
  - [x] `S3-EX-02` Add exception detail flyout/infolist with bilingual messaging and assignment controls.
  - [x] `S3-EX-03` Implement SLA timers and countdown badges with Livewire polling + i18n strings.
  - [x] `S3-EX-04` Wire activity log feed via audit trail data scoped per tenant.
  - [x] `S3-EX-05` Extend notifications queue for EN/AR templates on status and assignee changes.
  - [ ] `S3-EX-06` Add Pest coverage for exception actions, resolution flow, and SLA breach events.
  - `S3-KPI-07` Create KPI dashboard page housing Chart.js widgets for throughput/exceptions.
  - `S3-KPI-08` Implement aggregation queries/API endpoints powering dashboard datasets.
  - `S3-KPI-09` Apply locale-aware number/date formatting, including Arabic numerals.
  - `S3-KPI-10` Enable RTL chart mirroring and validate legend placement across locales.
  - `S3-UI-11` Craft showcase landing layout with localized hero, stats, and CTAs.
  - `S3-UI-12` Build shared Filament theme overrides covering typography, spacing, RTL variants.
  - `S3-UI-13` Localize navigation, buttons, empty states, and persist locale switcher state.
  - `S3-UI-14` Enforce responsive breakpoints and accessibility (contrast, focus states).
  - `S3-UI-15` Integrate PWA polish: offline toast, install prompt copy, localized manifest assets.
  - `S3-UI-16` Run Lighthouse audits (desktop/mobile) and commit configuration tweaks.
  - `S3-SEC-17` Harden audit retention policies and document DPIA checklist updates.
  - `S3-SEC-18` Verify encryption-at-rest for exception payloads with regression tests.
  - `S3-PERF-19` Load-test importer/validation queues and record baseline metrics.
  - `S3-PERF-20` Tune Horizon worker scaling and author Prometheus alert definitions.
  - [x] `S3-DEVOPS-21` Finalize blue/green deployment scripts and codify manual GitHub secret rotation (Terraform out of scope).
  - [x] `S3-DEVOPS-22` Document manual secret rotation runbook updates in ops playbook.
  - [x] `S3-DEVOPS-23` Expand CI to run RTL/Lighthouse jobs for both locales.
  - [x] `S3-UAT-24` Prepare bilingual UAT checklist and stakeholder walkthrough deck.
  - [x] `S3-UAT-25` Capture UAT feedback loop and triage issues into hotfix tasks.
- **Exit Criteria:** Exceptions resolved within SLA in staging, KPIs live, PWA & RTL acceptance criteria signed off, security checklist closed, go-live plan approved.

## Detailed Task Backlog (per Workstream)
- **Tenant & Security:** tenancy middleware, policy enforcement hooks, data seeding scripts, penetration test preparation.
- **Localization, RTL & Experience:** translation file management, RTL component audits, responsive breakpoints, accessibility reviews, Lighthouse PWA/regression automation.
- **Data Import & Quality:** file schema registry, anomaly detection (for example, missing salary fields), resumable imports, data purge policy.
- **Validation & SIF Engine:** configuration UI for rule toggles, rule version history, bank profile testing harness, scheduled re-validation jobs.
- **Exceptions & Reporting:** exception triage states (new/in review/resolved), SLA breach alerts, export reconciliation reports, operational dashboards (tooling TBD).
- **Platform & DevOps:** GitHub Actions workflows (lint/test/deploy), IaC for database/redis buckets, incident response runbooks, SAST/DAST gating, observability dashboards.

## Team and Responsibilities
- PM (Accountable): roadmap alignment, stakeholder reporting, risk management.
- PHP Lead (Responsible): architecture decisions, code reviews, milestone delivery.
- Backend Engineer: feature implementation across imports, validation, exports, reporting.
- QA (Shared): test strategy, regression packs, UAT coordination.
- DevOps (Shared): environment provisioning, CI/CD, monitoring integration.
- InfoSec (Consulted): encryption, audit, DPIA sign-offs.
- Support Lead (Informed): runbooks, on-call preparation.

## Risks and Mitigations
- Bank format drift: maintain versioned profile definitions, automated regression tests on export outputs.
- Regulatory changes: externalize rule definitions, schedule monthly compliance review, document change log.
- Data quality gaps: enforce schema validation, highlight anomalies pre-import, track exception SLA.
- Queue bottlenecks: size Horizon workers, add Prometheus alerts on batch duration, support manual rerun tooling.
- Localization/PWA regressions: include lighthouse/i18n checks in CI, schedule bilingual usability testing.
- Tenant isolation defects: add multi-tenant test coverage, perform penetration test focused on access control.

## KPIs
- Payroll batches processed on time (percent meeting submission window).
- Exception resolution lead time (average hours per exception).
- WPS/Mudad rejection rate (percent of submissions rejected by authority/bank).
- MTTR for failed exports.
- PWA Lighthouse scores (performance/accessibility/best practices/SEO) for desktop and mobile.

## Immediate Next Actions
1. Validate deployment topology and free-tier usage assumptions; refine Sprint 0 runbook with DevOps and InfoSec.
2. Confirm bilingual content sources and RTL design requirements with stakeholders; collect sample Arabic payroll artifacts.
3. Specify PWA acceptance criteria (offline scope, caching strategy, install prompts) and align with QA on automated checks.
