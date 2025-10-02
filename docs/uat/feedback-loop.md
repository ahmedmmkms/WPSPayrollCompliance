# UAT Feedback Loop / آلية التغذية الراجعة

## Intake Channels / قنوات الاستقبال
- Notion form (EN/AR) to capture scenario, locale, severity, attachments.
- Dedicated Slack channel `#wps-uat` mirrored with Arabic summaries for bilingual stakeholders.
- Optional email alias `uat@wps-payroll.example` for regulators who prefer email trails.

## Triage Cadence / وتيرة الفرز
1. Daily 09:00 GST stand-up — QA reviews new submissions, tags owner (Dev, Localization, DevOps).
2. Severity 1 issues trigger immediate hotfix branch with GitHub secret verification if credentials touched.
3. Severity 2/3 added to sprint board under "UAT Hotfix" column; include locale tags (`en`, `ar`).

## Tracking Template / قالب المتابعة
| Field (EN) | الحقل (AR) | Notes |
| --- | --- | --- |
| Scenario ID | معرّف السيناريو | Reference checklist row. |
| Locale | اللغة | `en`, `ar`, or `dual`. |
| Severity | درجة الخطورة | 1 (blocker) → 3 (minor). |
| Owner | المسؤول | Engineer on call + reviewer. |
| ETA | الوقت المتوقع | Target fix window. |
| Verification Evidence | دليل التحقق | Screenshot, log, or test case link. |

## Hotfix Flow / آلية الإصلاح السريع
1. Create branch `hotfix/<issue-key>` seeded from `main`.
2. Add Pest regression test or fixture replicating the bug when feasible.
3. Run `ci.yml` (includes Lighthouse EN/AR) before merge; ensure GitHub secrets stay unchanged unless the fix requires rotation.
4. Merge with squash, trigger blue/green deploy, and confirm previous symlink available for rollback.

## Closure / الإغلاق
- QA signs off in the Notion ticket and updates `docs/uat/checklist.md` status box.
- Stakeholder notified in English and Arabic summary within Slack/email.
- Archive artefacts (screenshots, reports) in `/docs/releases/<version>/uat/` for audit readiness.
