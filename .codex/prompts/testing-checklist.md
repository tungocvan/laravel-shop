# Testing Checklist Prompt

You are adding or reviewing tests for a Laravel 12 module.

## Test Areas

- module route boot
- authorization allow and deny paths
- validation failures
- Livewire render and actions
- service success and failure paths
- transactions and retry behavior
- import valid file
- import invalid headers
- import invalid rows
- export authorization
- export bounded memory behavior
- migration fresh install
- model relationships
- queue jobs
- cache invalidation
- storage paths

## Commands

Prefer targeted commands first:

```bash
php artisan test --filter=<RelevantTest>
vendor/bin/pint --test
npm run build
```

Then broader suites when risk justifies it:

```bash
composer test
php artisan test
```

Document commands that cannot run and why.
