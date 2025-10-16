<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_boms', function (Blueprint $table) {
            $table->decimal('tax_percent', 5, 2)->default(0)->after('note');
            $table->decimal('total_operational_cost', 20, 2)->default(0)->after('tax_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_boms', function (Blueprint $table) {
            $table->dropColumn(['tax_percent', 'total_operational_cost']);
        });
    }
};
