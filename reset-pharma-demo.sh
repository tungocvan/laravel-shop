#!/usr/bin/env bash
set -euo pipefail

# Local-only Pharma inventory/commission demo reset. Never run against production.
cd "$(dirname "$0")"
mode="${1:---dry-run}"
if [[ "$mode" != "--dry-run" && "$mode" != "--reset" ]]; then
  echo "Usage: bash reset-pharma-demo.sh [--dry-run|--reset]" >&2
  exit 2
fi
if [[ ! -f artisan || ! -f bootstrap/app.php ]]; then
  echo "Laravel root not found" >&2
  exit 2
fi
PHARMA_DEMO_RESET_MODE="$mode" php <<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$mode = getenv('PHARMA_DEMO_RESET_MODE');
$connection = DB::connection();
$config = $connection->getConfig();
$host = strtolower((string)($config['host'] ?? ''));
$database = (string)$connection->getDatabaseName();
$environment = app()->environment();

if ($environment !== 'local' || !in_array($host, ['localhost','127.0.0.1','::1'], true)
    || !in_array($connection->getDriverName(), ['mysql','mariadb'], true)) {
    fwrite(STDERR, "BLOCKED: requires APP_ENV=local, localhost MySQL/MariaDB. Current env={$environment}, host={$host}.\n");
    exit(3);
}
if ($database === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
    fwrite(STDERR, "BLOCKED: unexpected database name.\n");
    exit(3);
}

// Preserve medicine catalog, warehouses, bidding, price lists, partners and users.
$tables = [
    'pharma_inventory_issue_document_shares',
    'pharma_inventory_issue_documents',
    'pharma_inventory_receipt_document_shares',
    'pharma_inventory_receipt_documents',
    'pharma_inventory_issue_deferred_supplies',
    'pharma_inventory_issue_commissions',
    'pharma_inventory_issue_items',
    'pharma_inventory_receipt_items',
    'pharma_inventory_transactions',
    'pharma_inventory_balances',
    'pharma_inventory_issues',
    'pharma_inventory_receipts',
];
foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        fwrite(STDERR, "BLOCKED: required table missing: {$table}. Run migrations first.\n");
        exit(3);
    }
}

// Discover every real FK pointing into the reset set, including cross-module tables.
$references = DB::select(
    "SELECT TABLE_NAME AS child_table, REFERENCED_TABLE_NAME AS parent_table
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_SCHEMA = ?
       AND REFERENCED_TABLE_NAME IS NOT NULL",
    [$database, $database]
);
$set = array_fill_keys($tables, true);
foreach ($references as $ref) {
    if (isset($set[$ref->parent_table]) && !isset($set[$ref->child_table])) {
        fwrite(STDERR, "BLOCKED: external FK {$ref->child_table} -> {$ref->parent_table}. Audit dependency before reset.\n");
        exit(4);
    }
}
// Determine child-first order automatically; do not disable foreign key checks.
$remaining = array_fill_keys($tables, true);
$order = [];
while ($remaining) {
    $ready = [];
    foreach (array_keys($remaining) as $table) {
        $hasChild = false;
        foreach ($references as $ref) {
            if ($ref->parent_table === $table && $ref->child_table !== $table && isset($remaining[$ref->child_table])) {
                $hasChild = true;
                break;
            }
        }
        if (!$hasChild) $ready[] = $table;
    }
    if (!$ready) {
        fwrite(STDERR, "BLOCKED: circular FK dependency detected.\n");
        exit(4);
    }
    foreach ($ready as $table) {
        $order[] = $table;
        unset($remaining[$table]);
    }
}

echo "Pharma demo reset: env={$environment}, DB={$database}, host={$host}\n";
$total = 0;
foreach ($order as $table) {
    $count = DB::table($table)->count();
    $total += $count;
    printf("  %-53s %d\n", $table, $count);
}
echo "Total rows to remove: {$total}\n";
echo "Preserved: medicines, warehouses, bidding, price lists, partners, users, settings.\n";
echo "WARNING: external business references without database foreign keys cannot be detected.\n";
if ($mode === '--dry-run') {
    echo "DRY RUN ONLY. No data changed.\n";
    exit(0);
}
$tty = @fopen('/dev/tty', 'r+');
if (!$tty) {
    fwrite(STDERR, "BLOCKED: reset requires interactive terminal.\n");
    exit(5);
}
fwrite($tty, "Confirm an independent verified backup exists. Type BACKUP READY: ");
if (trim((string)fgets($tty)) !== 'BACKUP READY') exit(5);
fwrite($tty, "Type the exact database name ({$database}) to delete {$total} rows: ");
if (trim((string)fgets($tty)) !== $database) exit(5);
fclose($tty);
DB::transaction(function () use ($order) {
    foreach ($order as $table) DB::table($table)->delete();
});
echo "RESET COMPLETE. Re-run --dry-run to verify zero rows.\n";
PHP
