<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('fulfillment_status', 30)->nullable()->index();
            $table->unsignedInteger('order_version')->default(0);
            $table->string('payment_reference', 80)->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('carrier', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('pickup_location', 500)->nullable();
            $table->timestamp('pickup_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
        DB::table('sales')->whereNotNull('checkout_token')->where('status', 'completed')->update(['fulfillment_status' => 'pending']);
        DB::table('sales')->whereNotNull('checkout_token')->where('status', 'cancelled')->update(['fulfillment_status' => 'cancelled']);
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('note')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_by');
            $table->dropColumn(['fulfillment_status', 'order_version', 'payment_reference', 'paid_at', 'refunded_at', 'carrier', 'tracking_number', 'pickup_location', 'pickup_at', 'shipped_at', 'ready_at', 'completed_at']);
        });
    }
};
