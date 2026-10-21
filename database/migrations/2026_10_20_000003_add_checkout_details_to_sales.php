<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->uuid('checkout_token')->nullable()->unique();
            $table->json('delivery_details')->nullable();
            $table->string('delivery_method', 30)->nullable();
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_status', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['checkout_token']);
            $table->dropColumn(['checkout_token', 'delivery_details', 'delivery_method', 'shipping_cost', 'payment_method', 'payment_status']);
        });
    }
};
