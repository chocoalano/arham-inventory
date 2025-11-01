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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Public UUID for external references');

            // Basic identity
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->index();

            // Authentication (nullable if customers created by admin without password)
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token')->nullable();

            // Addresses are stored as JSON to support multiple fields and flexibility
            $table->json('billing_address')->nullable()->comment('JSON: street, city, postal_code, country, etc.');
            $table->json('shipping_address')->nullable()->comment('JSON: street, city, postal_code, country, etc.');

            // Business fields
            $table->string('company')->nullable();
            $table->string('vat_number')->nullable();

            // Ecommerce metrics
            $table->decimal('total_spent', 14, 2)->default(0)->comment('Total amount spent by customer');
            $table->unsignedInteger('orders_count')->default(0)->comment('Number of completed orders');
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->timestamp('last_order_at')->nullable();

            // Preferences / metadata
            $table->string('preferred_payment_method')->nullable();
            $table->json('metadata')->nullable();

            // Flags
            $table->boolean('is_active')->default(true);

            // Audit
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
