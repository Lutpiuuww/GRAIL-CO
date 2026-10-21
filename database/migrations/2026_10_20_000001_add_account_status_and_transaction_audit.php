<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
        });
        foreach (['sales', 'purchases'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['sales', 'purchases'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('cancelled_by');
                $table->dropColumn(['cancelled_at', 'cancellation_reason']);
            });
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};
