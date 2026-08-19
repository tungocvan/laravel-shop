<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('muasamcong_price_list_profiles');
    }

    public function down(): void
    {
        // Intentionally not recreated: Client price-list exports reuse
        // muasamcong_synced_export_profiles managed by /admin/muasamcong/synced.
    }
};
