# Module Review Checklist

## Architecture

- Module ownership is clear.
- Cross-module dependencies are documented.
- Controllers are thin.
- Livewire components do not own business rules.
- Services own workflows and transactions.
- Shared code is genuinely reusable.

## Routes And UI

- Routes are named and protected.
- Middleware is appropriate.
- Livewire aliases match provider registration.
- Blade views use existing layouts and components.
- Empty, loading, error, validation, and permission-denied states exist.

## Data

- Models have relationships and casts.
- Migrations are deterministic.
- Indexes support common filters.
- Multi-record writes are transactional.
- Deletions and cascades are intentional.

## Docs

- `ANALYSIS.md` is current.
- `INFORMATION.md` is current.
- `README.md` explains the module.
