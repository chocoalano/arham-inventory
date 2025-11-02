<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('raw_material_categories', function (Blueprint $table) {
            $table->string('slug', 2048)->after('is_active')->nullable();
            $table->string('image_url', 2048)->after('slug')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_material_categories', function (Blueprint $table) {
            $table->dropColumn('image_url');
            $table->dropColumn('slug');
        });
    }
};
