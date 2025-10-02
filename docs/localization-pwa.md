# Localization & PWA Acceptance Criteria

## Sprint 1 Implementation Snapshot
- Session-backed locale middleware with `/locale/{locale}` endpoint and bilingual landing/offline views.
- Filament admin inherits current locale; Arabic/English previews on landing + import notifications.
- PWA manifest exposed at `/manifest.webmanifest` with service worker caching `/` and `/offline`.
- Progressive enhancement: users receive offline fallback and install prompt readiness (Chromium/Android).

## Localization (English & Arabic)
- **Content coverage**: All end-user surfaces (navigation, table headers, validation errors, notification templates) available in English and Arabic.
- **Language switch**: Toggle persists via query parameter and session; default based on tenant preference.
- **Number & date formatting**: Uses locale-aware currency, digit grouping, and Hijri/Gregorian fallbacks where mandated.
- **RTL layout**: Component mirroring verified for forms, tables, charts, and print/export outputs. No clipped text on tablet/mobile breakpoints.
- **Accessibility**: Heading order, aria-labels, and focus states validated in both locales. Minimum contrast 4.5:1 maintained after RTL flip.

## Arabic Content Sources
- Payroll glossary vetted by compliance (UAE MOHRE + KSA Mudad terminology).
- Sample employee roster and WPS SIF template translated (stored in `/docs/quotas.md`).
- Notification copy approved by PM + Legal prior to Sprint 2 handoff.

## PWA Expectations
- **Offline scope**: App shell (navigation + recent batch listing) cached via Workbox; offline banner shown for queue-heavy screens.
- **Install prompts**: Custom install CTA for Chromium/Android with fallbacks; hides on unsupported browsers.
- **Caching strategy**: Static assets `Stale-While-Revalidate`; API calls use network-first with offline toast + retry.
- **Performance budgets**: Lighthouse (mobile) ≥ 80 for Performance & Best Practices, ≥ 90 for Accessibility & SEO.
- **Compliance**: PWA manifest includes bilingual name/description, right-to-left display overrides, and 512px maskable icon.

Update this document as acceptance tests evolve. QA owns the verification checklist; PM maintains content approvals.
