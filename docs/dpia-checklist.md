# DPIA Checklist Updates (Sprint 3)

> Tracks privacy controls introduced for exception management and audit retention policies (S3-SEC-17 & S3-SEC-18).

## Audit Trail Retention
- Audit metadata is persisted under `payroll_batches.metadata.audit` with Laravel encrypted casting.
- New configuration file `config/audit.php` exposes:
  - `AUDIT_TRAIL_MAX_EVENTS` (default `200`) – hard cap of retained events per batch.
  - `AUDIT_TRAIL_MAX_AGE_DAYS` (default `180`) – time-based pruning window.
  - `AUDIT_TRAIL_PRUNE_CHUNK` – chunk size used by maintenance command.
  - `AUDIT_TRAIL_POLICY_VERSION` – surfaced for documentation/version tracking.
- Automated pruning:
  - Artisan command `php artisan audit:prune` iterates tenant batches and enforces the retention rules.
  - Scheduled to run daily at 02:00 UTC alongside `horizon:snapshot`.
  - Supports `--dry-run` for evidence gathering without mutation.
- Regression coverage added in `tests/Unit/Audit/AuditTrailRecorderTest.php` and `tests/Feature/Audit/PruneAuditTrailsCommandTest.php` to guard retention behaviour.

## Exception Payload Encryption
- `PayrollException` model casts (`encrypted:array`) cover `message`, `context`, and `metadata` fields; regression test verifies ciphertext at rest.
- Test `tests/Feature/Exceptions/PayrollExceptionEncryptionTest.php` simulates tenant writes and asserts plaintext absence in the database layer.
- Result: Exception payloads (including freeform comments) remain encrypted at rest across tenants.

## Evidence & Next Steps
- Retention and encryption tests execute with `composer test`.
- Include `php artisan audit:prune --dry-run` output in release records for DPIA evidence when onboarding new tenants.
