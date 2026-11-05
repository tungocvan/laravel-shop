<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_medicine_excel_profiles', function (Blueprint $table): void {
            // Client inventory profiles use web-inventory-stock / web-inventory-movements.
            // The original owner_guard width cannot store these scoped identifiers.
            $table->string('owner_guard', 64)->change();
        });
    }

    public function down(): void
    {
        // Do not truncate stored owner guards during rollback.
        // Restoring the former short width would lose valid inventory profile data.
    }
};
