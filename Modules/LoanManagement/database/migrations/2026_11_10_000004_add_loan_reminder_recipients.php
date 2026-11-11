<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('loan_reminder_recipients')) {
            Schema::create('loan_reminder_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_contract_id')->constrained('loan_contracts')->restrictOnDelete();
            $table->string('email', 255);
            $table->timestamps();
            $table->unique(['loan_contract_id', 'email'], 'loan_reminder_recipient_unique');
            });
        }

        // Preserve the existing single recipient for every configured contract.
        foreach (DB::table('loan_reminder_settings')->select('loan_contract_id', 'recipient_email')->get() as $setting) {
            $email = mb_strtolower(trim((string) $setting->recipient_email));
            if ($email === '') continue;
            DB::table('loan_reminder_recipients')->insertOrIgnore([
                'loan_contract_id' => $setting->loan_contract_id,
                'email' => $email,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // MySQL may use the old unique index to support the schedule foreign key.
        // Create the replacement first, then drop the old one. Both steps are
        // guarded because MySQL DDL is not rolled back after a failed migration.
        $indexes = collect(Schema::getIndexes('loan_reminder_deliveries'));
        if (!$indexes->contains(fn (array $index): bool => $index['name'] === 'loan_reminder_delivery_recipient_uq')) {
            Schema::table('loan_reminder_deliveries', function (Blueprint $table): void {
                $table->unique(
                    ['loan_repayment_schedule_id', 'days_before', 'due_at_snapshot', 'recipient_email'],
                    'loan_reminder_delivery_recipient_uq'
                );
            });
        }

        $indexes = collect(Schema::getIndexes('loan_reminder_deliveries'));
        if ($indexes->contains(fn (array $index): bool => $index['name'] === 'loan_reminder_delivery_once_uq')) {
            Schema::table('loan_reminder_deliveries', function (Blueprint $table): void {
                $table->dropUnique('loan_reminder_delivery_once_uq');
            });
        }
    }

    public function down(): void
    {
        // Multiple recipient deliveries cannot be collapsed safely into the former unique key.
        // Explicitly prohibit destructive rollback instead of deleting accounting/audit rows.
        throw new \RuntimeException('Loan reminder recipients migration is forward-only; use a reviewed data-preserving rollback plan.');
    }
};
