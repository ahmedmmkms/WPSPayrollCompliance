# Queue Performance Baseline (S3-PERF-19)

Use the `queue:baseline` artisan command to capture importer and validation job throughput. The command
publishes JSON metrics (default `storage/app/metrics/queue-baseline.json`) and prints a summary table.

```bash
php artisan queue:baseline TENANT_ID --employees=250 --runs=3
```

## Output Structure
```json
{
    "generated_at": "2024-10-01T12:00:00+00:00",
    "queue_connection": "redis",
    "tenant_id": "tenant-uuid",
    "employees_per_run": 250,
    "runs": 3,
    "importer": {
        "runs": 3,
        "employees_per_run": 250,
        "durations_ms": [742.14, 735.01, 728.87],
        "average_ms": 735.34,
        "max_ms": 742.14,
        "min_ms": 728.87,
        "total_ms": 2206.02
    },
    "validation": {
        "runs": 3,
        "rule_sets": ["uae-wps-v1"],
        "durations_ms": [512.93, 498.65, 505.22],
        "average_ms": 505.6,
        "max_ms": 512.93,
        "min_ms": 498.65,
        "total_ms": 1516.8
    }
}
```

> Numbers above are illustrative; commit fresh results before go-live using production-like data volumes.

## Recommended Workflow
- Execute `queue:baseline` for staging and production tenants after major releases.
- Store generated JSON alongside release artefacts for historical trending (attach to runbooks/UAT notes).
- Feed max/average durations into Prometheus alert thresholds (see `docs/observability/prometheus-alerts.yml`).
- Combine with Horizon graphs to decide when to bump `HORIZON_IMPORTS_MAX_PROCESSES` or `HORIZON_VALIDATION_MAX_PROCESSES`.

## Quick Checks
- `--skip-importer` / `--skip-validation` allow isolating specific workloads.
- Use `--rule-sets=uae-wps-v1 --rule-sets=ksa-mudad-sandbox` to stress multi-set validation.
- For compliance evidence, run `php artisan queue:baseline TENANT_ID --runs=1 --output=metrics/baseline-YYYYMMDD.json` and attach JSON to DPIA records.
