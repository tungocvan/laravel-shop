# Docker Development PHPUnit runner

The production `app` target intentionally installs Composer dependencies with `--no-dev`. Its `vendor/bin/phpunit` and `php artisan test` are unavailable by design. The opt-in `test` Docker target installs dev dependencies in a separate image.

## Build (host, once after code changes)

```bash
docker compose -p laravel-app-dev -f compose.yaml -f compose.test.yaml --profile test build test
```

## Focused tests (host)

```bash
docker compose -p laravel-app-dev -f compose.yaml -f compose.test.yaml --profile test run --rm --no-deps test php artisan test tests/Feature/Pharma/OptionalExcelProfileMigrationContractTest.php
```

## Pharma regression (only after focused tests pass)

```bash
docker compose -p laravel-app-dev -f compose.yaml -f compose.test.yaml --profile test run --rm --no-deps test php artisan test Modules/Pharma/Tests/Unit/MedicineExcelProfileContractTest.php
```

The runner has no network, does not start or connect to the Compose MariaDB service, does not mount the host `.env` or named storage volumes, and uses SQLite in-memory defaults from `phpunit.xml`. It does not run migrations against `db_app`. Its source is baked into the test image: rebuild after each Git pull that changes source or tests.

Production services still target `app`, not `test`. Do not run `docker compose down -v`, `migrate:fresh`, or production database commands for testing.

Test results remain PENDING until the user provides actual output.
