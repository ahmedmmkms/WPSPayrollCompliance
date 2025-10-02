# Stakeholder Walkthrough Deck Outline / مخطط عرض أصحاب المصلحة

Use this structure when preparing the bilingual slide deck (Google Slides or Pitch). Each slide includes English and Arabic text side by side.

## Slide 1 — Title & Objectives / العنوان والأهداف
- EN: "WPS Payroll Compliance MVP — Sprint 3 UAT Readiness"
- AR: "الامتثال لنظام حماية الأجور — جاهزية اختبار القبول للنسخة الأولى"
- Key bullets: scope reminder, environments, sign-off owners.

## Slide 2 — Agenda / جدول الأعمال
- EN Bullets: Introduction, Demo Flow, Exception Handling, Reporting & KPIs, Next Steps.
- AR Bullets: مقدمة، تسلسل العرض، معالجة الاستثناءات، التقارير ومؤشرات الأداء، الخطوات التالية.

## Slide 3 — Environment Overview / نظرة على البيئة
- Highlight blue/green deployment structure, manual GitHub secret management, and tenant isolation.
- Include diagram snippet from `docs/topology.md` with Arabic labels on the right side.

## Slide 4 — Demo Script (EN) / سيناريو العرض (إنجليزي)
1. Login → Tenant switcher
2. Import payroll batch → validation results
3. Exception assignment → resolution with SLA badge
4. SIF export → download + offline banner

## Slide 5 — Demo Script (AR) / سيناريو العرض (عربي)
1. تسجيل الدخول → اختيار المستأجر
2. استيراد ملف الرواتب → نتائج التحقق
3. تعيين الاستثناء → إغلاق الاستثناء مع المؤقت
4. تصدير ملف SIF → التنزيل + إشعار عدم الاتصال

## Slide 6 — Exception Insights / رؤى الاستثناءات
- Stats chips, SLA timers, audit log clip (English left, Arabic right).
- Reference regression coverage (`tests/Feature/ExceptionFlowTest.php` when available).

## Slide 7 — KPI Dashboard / لوحة مؤشرات الأداء
- Screenshots for EN/AR with mirrored charts.
- Note localization of numerals and date formats.

## Slide 8 — Compliance & Security / الامتثال والأمان
- Summarise DPIA checklist updates, GitHub secret rotation process, audit retention tweaks.
- Provide bilingual bullet pairs for each control.

## Slide 9 — UAT Checklist & Roles / قائمة الاختبار والأدوار
- Link to `docs/uat/checklist.md`.
- Table of owners with English/Arabic labels.

## Slide 10 — Feedback & Next Steps / التغذية الراجعة والخطوات التالية
- Explain feedback loop (see `docs/uat/feedback-loop.md`).
- Outline go-live gates once hotfix triage closes.

Add appendix slides for screenshots or data extracts requested by regulators. Maintain bilingual speaker notes to support interpreters.
