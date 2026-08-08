# Performance Review Checklist

- Lists use pagination.
- Large exports use chunking, lazy collections, or queues.
- Large imports process rows incrementally.
- N+1 relationships are eager loaded.
- Query-count-sensitive pages have tests or manual profiling notes.
- Stable catalogs, menus, and settings have a cache policy.
- Cache invalidation happens from write services.
- Blade views do not run repeated permission or database queries.
- Jobs are idempotent and retry-aware.
- Generated files have retention cleanup.
