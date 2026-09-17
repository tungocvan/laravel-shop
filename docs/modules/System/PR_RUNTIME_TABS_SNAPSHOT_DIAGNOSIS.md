# PR Summary — System Runtime Tabs / Snapshot Diagnosis

## Summary

Fix `/admin/system` 500 caused by incomplete runtime tab overrides and improve Module Snapshot recovery diagnostics on `/admin/system/database`.

## Changes

- Defensive runtime component resolution in `SystemController`.
- System config overrides may only override core tab IDs; stale orphan `themes` override removed.
- Regression coverage for runtime tab contract.
- Snapshot UI replaces generic `BLOCKED` with explicit schema mismatch reason, validated-package context, and remediation guidance.
- Restore remains compatible-only.
- Regression coverage for recovery diagnosis.

## Validation

Operator: targeted tests PASS; UI PASS on 2026-09-17.

## Review note

The Blade raw line statistics are inflated by formatting compaction in the diagnosis commit. Semantic review confirmed existing Database Manager actions remain present. No restore-safety bypass is introduced.