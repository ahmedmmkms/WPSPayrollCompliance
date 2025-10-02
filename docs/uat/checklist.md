# Sprint 3 UAT Checklist / قائمة التحقق لاختبار القبول

> English ↔ العربية. Tick every box before promoting to production.

## Pre-UAT Environment Gate / تجهيز بيئة الاختبار

| Checklist (EN) | قائمة التحقق (AR) | Owner | Status |
| --- | --- | --- | --- |
| Provision dedicated UAT tenant seeded with anonymised payroll data. | إنشاء مستأجر مخصص لاختبارات UAT مع بيانات رواتب منزوعة الهوية. | DevOps | [ ] |
| Refresh PlanetScale branch and apply latest migrations. | تحديث فرع PlanetScale وتطبيق آخر عمليات الترحيل. | DevOps | [ ] |
| Load translated copy for EN/AR locales (lang/ files + Filament resources). | تحميل المحتوى المترجم للغتين (ملفات lang/ + صفحات Filament). | Localization | [ ] |
| Warm queues and verify Upstash quota headroom for import/export jobs. | تهيئة الطوابير والتأكد من توفر السعة في Upstash لمهام الاستيراد/التصدير. | Ops | [ ] |
| Confirm GitHub secrets updated (PlanetScale/Upstash) and blue/green deploy ran without error. | التأكد من تحديث أسرار GitHub (PlanetScale/Upstash) وتشغيل النشر الأزرق/الأخضر دون أخطاء. | DevOps | [ ] |

## Core Scenarios / السيناريوهات الأساسية

| Scenario (EN) | السيناريو (AR) | Expected Evidence |
| --- | --- | --- |
| Upload bilingual payroll batch (CSV) and validate against UAE WPS rules. | رفع ملف رواتب ثنائي اللغة (CSV) والتحقق من مطابقته لقواعد نظام حماية الأجور في الإمارات. | Validation report JSON + screenshots. |
| Trigger exception workflow (assign → resolve) with SLA timer in both locales. | تنفيذ مسار الاستثناء (تعيين → حل) مع مؤقت SLA باللغتين. | Activity log entry + notification copy. |
| Generate SIF export and download summary while offline banner appears correctly. | إنشاء ملف SIF وتنزيل الملخص مع ظهور شريط عدم الاتصال بشكل صحيح. | Generated SIF + PWA toast capture. |
| Switch locale toggle across Filament dashboards and confirm RTL layout. | تبديل اللغة من خلال لوحة التحكم في Filament والتأكد من تخطيط RTL. | Screen recording (EN ↔ AR). |
| Validate KPI dashboard renders mirrored charts and formatted numerals. | التأكد من عرض لوحة مؤشرات الأداء مع مخططات معكوسة وأرقام منسقة. | Lighthouse report + screenshots. |

## Regression Sweeps / اختبارات الانحدار

- [ ] **EN Regression Pack / حزمة الانحدار الإنجليزية** → Execute smoke tests across login, imports, exceptions, exports.
- [ ] **AR Regression Pack / حزمة الانحدار العربية** → Repeat flows ensuring Arabic copy, numerals, and RTL spacing.
- [ ] **Accessibility / إمكانية الوصول** → Confirm keyboard traversal, focus states, contrast ratios ≥ 4.5.
- [ ] **Performance / الأداء** → Review Lighthouse EN/AR scores ≥ configured thresholds (see `.github/workflows/ci.yml`).

## Sign-off / الاعتماد النهائي

| Role | التوقيع | Date / التاريخ |
| --- | --- | --- |
| Product Owner | ____________________ | __________ |
| Compliance Lead | ____________________ | __________ |
| QA Lead | ____________________ | __________ |

Document final approval in the sprint retro notes and archive artefacts in the release folder.
