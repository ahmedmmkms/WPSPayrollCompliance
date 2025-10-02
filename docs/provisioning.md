## 10. Infrastructure Assets
- Native PHP 8.3 runtime is used locally; ensure the `pgsql`, `mysqli`, `pdo_mysql`, `intl`, and `bcmath` extensions are installed on the host before running `php artisan serve`.
- Seed a local operations admin by setting the `OPS_ADMIN_*` variables in `.env` and GitHub settings for automated deployments.
- Create tenants via `php artisan tenants:create --name=Acme --domain=acme.example.com --email=ops@acme.com`.
- GitHub repository secrets remain manual; follow the rotation checklist in `docs/runbook.md` when credentials change.

## 11. Deployment Status (2025-09-25)
- [done] Localization & PWA acceptance criteria captured in `docs/localization-pwa.md` (QA owner).
- [done] Accounts created for Alwaysdata, PlanetScale/Neon, Upstash, Vercel; credentials tracked in docs/accounts.md.
- [done] GitHub Actions secrets validated in repository settings; maintain rotation log in `docs/accounts.md`.
- [done] Alwaysdata web application deployed using the native PHP runtime (`https://wpspayrollcompliance.alwaysdata.net`).
- [done] Bilingual placeholder (`public/index.php`) deployed so Alwaysdata health checks succeed until Laravel app ships.
- [done] Deploy workflow triggers `git push` to Alwaysdata; secrets required: `ALWAYSDATA_DEPLOY_KEY`.
