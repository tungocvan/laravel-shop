# Migration And Model Prompt

You are creating or changing database schema and Eloquent models for a Laravel 12 module.

## Rules

- Put module migrations under `Modules/<ModuleName>/database/migrations`.
- Put models under `Modules/<ModuleName>/Models`.
- Use explicit table names when module naming could be ambiguous.
- Add fillable or guarded intentionally.
- Add casts for dates, booleans, JSON, decimals, enums, and encrypted fields.
- Define relationships in both directions when useful.
- Add indexes for foreign keys, lookup columns, status columns, and frequent filters.
- Avoid destructive schema changes without a migration and rollout plan.
- Keep migrations deterministic for fresh install.

## Output

Document:

- table purpose
- columns
- indexes
- relationships
- cascade behavior
- migration risk
- rollback behavior
